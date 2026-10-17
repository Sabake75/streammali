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
 * Refuses to run if any Payout exists: GetCreatorBalance computes a
 * creator's balance as sum(net_amount) minus already-paid payouts, so
 * deleting the ledger entries a real payout was computed against would
 * silently corrupt that creator's balance.
 */
class PurgePaymentsBeforeToday extends Command
{
    protected $signature = 'payments:purge-before-today {--force : Supprime réellement (sans ce flag, affiche seulement un rapport)}';

    protected $description = "Supprime les paiements et écritures de ledger créés avant aujourd'hui (nettoyage des ventes de test avant lancement public)";

    public function handle(): int
    {
        if (Payout::query()->exists()) {
            $this->error('Au moins une demande de retrait existe déjà — abandon. Supprimer ces paiements de test rendrait les soldes créateur incohérents.');

            return self::FAILURE;
        }

        $cutoff = now()->startOfDay();
        $payments = Payment::query()->where('created_at', '<', $cutoff)->get();

        if ($payments->isEmpty()) {
            $this->info("Aucun paiement créé avant aujourd'hui.");

            return self::SUCCESS;
        }

        $paymentIds = $payments->pluck('id');
        $ledgerCount = LedgerEntry::query()->whereIn('payment_id', $paymentIds)->count();

        $this->table(
            ['Statut', 'Nombre'],
            $payments->groupBy('status')->map(fn ($group) => $group->count())->map(fn ($count, $status) => [$status, $count])->values(),
        );
        $this->line("→ {$payments->count()} paiement(s), {$ledgerCount} écriture(s) de ledger associée(s).");

        if (! $this->option('force')) {
            $this->warn('Dry-run : relance avec --force pour supprimer réellement.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($paymentIds) {
            LedgerEntry::query()->whereIn('payment_id', $paymentIds)->delete();
            Payment::query()->whereIn('id', $paymentIds)->delete();
        });

        $this->info('Suppression effectuée.');

        return self::SUCCESS;
    }
}
