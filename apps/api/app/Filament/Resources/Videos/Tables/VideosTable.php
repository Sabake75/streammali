<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Moderation\Models\Report;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Notifications\VideoStatusChanged;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Créateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.label')
                    ->label('Catégorie')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Prix')
                    ->suffix(' FCFA'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (VideoStatus $state) => $state->color())
                    ->formatStateUsing(fn (VideoStatus $state) => $state->label()),
                TextColumn::make('source_status')
                    ->label('Fichier vidéo')
                    ->badge()
                    ->color(fn (VideoSourceStatus $state) => $state->color())
                    ->formatStateUsing(fn (VideoSourceStatus $state) => $state->label()),
                // ->boolean()'s stock icons (check-circle/x-circle,
                // success/danger) read as "OK vs. error" — wrong for a
                // status that's just off by default, not a problem.
                // Étoile pleine/vide, comme l'icône de l'action "Mettre
                // en avant" plus bas, pour rester cohérent visuellement.
                // ->action() rend l'icône elle-même cliquable pour
                // basculer directement, sans passer par le sous-menu —
                // sans ça, cliquer dessus ne fait rien d'autre
                // qu'ouvrir la page d'édition (comportement par défaut
                // de Filament au clic sur une ligne).
                IconColumn::make('featured_at')
                    ->label('En vedette')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->featured_at !== null)
                    ->action(function ($record) {
                        if ($record->status !== VideoStatus::Approved) {
                            Notification::make()
                                ->title('Seules les vidéos validées peuvent être mises en avant.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->update(['featured_at' => $record->featured_at ? null : now()]);
                    }),
                TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('pending_reports_count')
                    ->label('Signalements')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function ($record) {
                        $count = static::pendingReportsCount($record);

                        return $count > 0 ? $count : null;
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(VideoStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'label'),
                TernaryFilter::make('reported')
                    ->label('Signalée')
                    ->queries(
                        true: fn ($query) => $query->whereHas('reports', fn ($q) => $q->where('status', ReportStatus::Pending)),
                        false: fn ($query) => $query->whereDoesntHave('reports', fn ($q) => $q->where('status', ReportStatus::Pending)),
                    ),
            ])
            ->recordActions([
                // Hors du menu "Actions" (plutôt qu'à l'intérieur avec le
                // reste) : c'est le premier geste attendu d'un modérateur
                // avant de décider quoi que ce soit, pas une action
                // secondaire à aller chercher dans un sous-menu.
                Action::make('watch')
                    ->label('Visionner')
                    ->icon('heroicon-o-play-circle')
                    ->color('gray')
                    ->visible(fn ($record) => $record->source_status === VideoSourceStatus::Ready && $record->playback_url !== null)
                    ->modalHeading(fn ($record) => $record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->schema(fn ($record) => [
                        View::make('filament.videos.player')
                            ->viewData(['src' => static::playerEmbedUrl($record)]),
                    ]),
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Valider')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== VideoStatus::Approved)
                        // Rien à valider tant qu'il n'y a pas de fichier prêt à visionner.
                        ->disabled(fn ($record) => $record->source_status !== VideoSourceStatus::Ready)
                        ->tooltip(fn ($record) => $record->source_status !== VideoSourceStatus::Ready
                            ? "Le fichier vidéo n'est pas encore prêt."
                            : null)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => VideoStatus::Approved,
                                'rejection_reason' => null,
                            ]);
                            $record->creator->notify(new VideoStatusChanged($record));
                        }),
                    Action::make('reject')
                        ->label('Refuser')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status !== VideoStatus::Rejected)
                        ->schema([
                            Textarea::make('rejection_reason')
                                ->label('Motif du refus')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => VideoStatus::Rejected,
                                'rejection_reason' => $data['rejection_reason'],
                            ]);
                            $record->creator->notify(new VideoStatusChanged($record));
                        }),
                    Action::make('toggle_featured')
                        ->label(fn ($record) => $record->featured_at ? 'Retirer de la mise en avant' : 'Mettre en avant')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->visible(fn ($record) => $record->status === VideoStatus::Approved)
                        ->action(fn ($record) => $record->update([
                            'featured_at' => $record->featured_at ? null : now(),
                        ])),
                    Action::make('reports')
                        ->label('Signalements')
                        ->icon('heroicon-o-flag')
                        ->color('danger')
                        ->visible(fn ($record) => static::pendingReportsCount($record) > 0)
                        ->schema(fn ($record) => [
                            TextEntry::make('reports_list')
                                ->hiddenLabel()
                                ->state(fn () => static::formatReports($record))
                                ->html(),
                        ])
                        ->modalSubmitActionLabel('Marquer traités')
                        ->action(fn ($record) => $record->reports()
                            ->where('status', ReportStatus::Pending)
                            ->update(['status' => ReportStatus::Dismissed])),
                    EditAction::make(),
                ]),
            ]);
    }

    /**
     * Cloudflare Stream's manifest URL (used by the web/mobile HLS players)
     * has an equivalent iframe embed at the same path minus
     * "/manifest/video.m3u8" — no extra Cloudflare config needed, and it
     * comes with its own player UI (works without hls.js in the admin panel).
     *
     * Rendered via a dedicated Blade view (not TextEntry::html()) because
     * Filament runs html() state through Symfony's HtmlSanitizer, which
     * strips <iframe> by default — the modal would otherwise open empty.
     */
    private static function playerEmbedUrl(Video $video): string
    {
        return preg_replace('#/manifest/video\.m3u8$#', '/iframe', (string) $video->playback_url);
    }

    private static function pendingReportsCount(Video $video): int
    {
        return $video->reports()->where('status', ReportStatus::Pending)->count();
    }

    private static function formatReports(Video $video): string
    {
        $reports = $video->reports()->with('reporter')->where('status', ReportStatus::Pending)->oldest()->get();

        return $reports
            ->map(function (Report $report) {
                $reporter = e($report->reporter->name);
                $when = $report->created_at->format('d/m/Y H:i');
                $reason = nl2br(e($report->reason));

                return "<strong>{$reporter}</strong> <span style=\"color:#888\">({$when})</span><br>{$reason}";
            })
            ->implode('<hr style="margin:8px 0">');
    }
}
