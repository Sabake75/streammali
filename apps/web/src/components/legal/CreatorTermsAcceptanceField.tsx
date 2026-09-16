"use client";

import { useState } from "react";
import { CreatorTermsContent } from "@/components/legal/CreatorTermsContent";
import { TermsModal } from "@/components/legal/TermsModal";

/**
 * Checkbox + "read the terms" modal, shared between the two creator
 * registration flows (brand-new account vs. upgrading an existing viewer
 * account) — previously duplicated verbatim in both.
 */
export function CreatorTermsAcceptanceField({
  accepted,
  onAcceptedChange,
}: {
  accepted: boolean;
  onAcceptedChange: (accepted: boolean) => void;
}) {
  const [modalOpen, setModalOpen] = useState(false);

  return (
    <>
      <label className="flex items-start gap-2 text-sm text-neutral-600 dark:text-neutral-400">
        <input
          type="checkbox"
          required
          checked={accepted}
          onChange={(event) => onAcceptedChange(event.target.checked)}
          className="mt-0.5 h-4 w-4 shrink-0 accent-orange-600"
        />
        <span>
          J&apos;ai lu et j&apos;accepte les{" "}
          <button
            type="button"
            onClick={() => setModalOpen(true)}
            className="font-medium text-orange-600 underline hover:no-underline dark:text-orange-400"
          >
            CGU créateur
          </button>
        </span>
      </label>

      <TermsModal
        open={modalOpen}
        title="Conditions générales d'utilisation : Créateur"
        onClose={() => setModalOpen(false)}
        onAccept={() => {
          onAcceptedChange(true);
          setModalOpen(false);
        }}
      >
        <CreatorTermsContent />
      </TermsModal>
    </>
  );
}
