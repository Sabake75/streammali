<?php

namespace App\Console\Commands;

use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup before the public launch: every Payment/LedgerEntry so far
 * is dev/sandbox test data, inflating the moderator dashboard's "chiffre
 * d'affaires"/solde stats. Dry-run by default; `--force` actually deletes.
 *
 * Refuses to run if any Payout exists, unless `--include-paid-payouts` is
 * also passed: GetCreatorBalance computes a creator's balance as
 * sum(net_amount) minus pending/paid payouts, so deleting the ledger
 * entries a payout was computed against would silently corrupt that
 * creator's balance — `--include-paid-payouts` is an explicit
 * acknowledgment that no real money actually moved for those payouts
 * (the disbursement itself is a manual step outside the app, see
 * App\Domain\Payment\README.md — a "paid" status alone doesn't prove it).
 */
class PurgePaymentsBeforeToday extends Command
{
    protected $signature = 'payments:purge-before-today
        {--force : Supprime réellement (sans ce flag, affiche seulement un rapport)}
        {--include-paid-payouts : Supprime aussi les demandes de retrait (même "paid") créées avant aujourd\'hui — à utiliser seulement si aucun argent réel n\'a été envoyé}';

    protected $description = "Supprime les paiements, écritures de ledger et (optionnellement) demandes de retrait créés avant aujourd'hui (nettoyage des ventes de test avant lancement public)";

    public function handle(): int
    {
        $cutoff = now()->startOfDay();
        $includePayouts = (bool) $this->option('include-paid-payouts');

        $payoutsBefore = Payout::query()->where('created_at', '<', $cutoff)->get();
        if ($payoutsBefore->isNotEmpty() && ! $includePayouts) {
            $this->error('Au moins une demande de retrait existe déjà — abandon. Relance avec --include-paid-payouts seulement si aucun argent réel n\'a été envoyé pour ces retraits.');

            return self::FAILURE;
        }

        $payments = Payment::query()->where('created_at', '<', $cutoff)->get();

        if ($payments->isEmpty() && $payoutsBefore->isEmpty()) {
            $this->info("Rien à supprimer avant aujourd'hui.");

            return self::SUCCESS;
        }

        $paymentIds = $payments->pluck('id');
        $ledgerCount = LedgerEntry::query()->whereIn('payment_id', $paymentIds)->count();

        if ($payments->isNotEmpty()) {
            $this->table(
                ['Statut paiement', 'Nombre'],
                $payments->groupBy('status')->map(fn ($group) => $group->count())->map(fn ($count, $status) => [$status, $count])->values(),
            );
        }
        $this->line("→ {$payments->count()} paiement(s), {$ledgerCount} écriture(s) de ledger associée(s).");

        if ($payoutsBefore->isNotEmpty()) {
            $this->table(
                ['Statut retrait', 'Nombre'],
                $payoutsBefore->groupBy('status')->map(fn ($group) => $group->count())->map(fn ($count, $status) => [$status, $count])->values(),
            );
        }

        if (! $this->option('force')) {
            $this->warn('Dry-run : relance avec --force pour supprimer réellement.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($paymentIds, $payoutsBefore) {
            LedgerEntry::query()->whereIn('payment_id', $paymentIds)->delete();
            Payment::query()->whereIn('id', $paymentIds)->delete();
            Payout::query()->whereIn('id', $payoutsBefore->pluck('id'))->delete();
        });

        $this->info('Suppression effectuée.');

        return self::SUCCESS;
    }
}
