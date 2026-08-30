import { TermsSection } from "./TermsSection";

export function PrivacyPolicyContent() {
  return (
    <div className="flex flex-col gap-8">
      <TermsSection title="Objet">
        <p>
          Cette politique explique quelles données StreamMali collecte, pourquoi, et comment tu
          peux les consulter, corriger ou faire supprimer. Elle complète les{" "}
          <a href="/cgu-spectateur" className="text-orange-600 hover:underline dark:text-orange-400">
            CGU spectateur
          </a>{" "}
          et les{" "}
          <a href="/cgu-createur" className="text-orange-600 hover:underline dark:text-orange-400">
            CGU créateur
          </a>
          , qui restent la référence pour les règles d&apos;usage de la plateforme.
        </p>
      </TermsSection>

      <TermsSection title="Ce que nous collectons">
        <p>
          <strong>Tout le monde :</strong> nom et numéro de téléphone, à l&apos;inscription.
        </p>
        <p>
          <strong>Créateurs uniquement :</strong> une pièce d&apos;identité, pour vérifier que tu es
          bien le propriétaire des contenus que tu publies. Elle n&apos;est jamais visible
          publiquement — seule la modération y a accès.
        </p>
        <p>
          <strong>Paiement :</strong> lors d&apos;un achat ou d&apos;une demande de retrait, ton
          numéro Mobile Money est transmis à PayDunya (notre prestataire de paiement) pour traiter
          la transaction. StreamMali ne stocke aucune information bancaire ou Mobile Money
          au-delà de ce numéro.
        </p>
        <p>
          <strong>Usage :</strong> les vidéos vues, achetées, mises en favori, et les avis laissés
          — pour faire fonctionner ton compte (bibliothèque, recommandations, historique).
        </p>
      </TermsSection>

      <TermsSection title="Pourquoi nous les collectons">
        <p>
          Uniquement pour faire fonctionner StreamMali : créer et sécuriser ton compte, traiter tes
          paiements et tes retraits, permettre la modération des contenus, et te montrer des
          recommandations pertinentes. Aucune donnée n&apos;est collectée à des fins publicitaires.
        </p>
      </TermsSection>

      <TermsSection title="Avec qui elles sont partagées">
        <p>
          Tes données ne sont jamais revendues. Elles sont partagées uniquement avec les
          prestataires nécessaires au fonctionnement du service :
        </p>
        <ul className="list-disc pl-5">
          <li>
            <strong>PayDunya</strong> — traitement des paiements et retraits Mobile Money.
          </li>
          <li>
            <strong>Cloudflare Stream</strong> — hébergement et diffusion des vidéos.
          </li>
        </ul>
      </TermsSection>

      <TermsSection title="Combien de temps nous les gardons">
        <p>
          Tant que ton compte existe. Si tu demandes la suppression de ton compte, tes données
          personnelles (nom, téléphone, pièce d&apos;identité) sont supprimées ; les enregistrements
          de transactions peuvent être conservés plus longtemps quand la loi l&apos;exige
          (comptabilité, lutte contre la fraude).
        </p>
      </TermsSection>

      <TermsSection title="Sécurité">
        <p>
          Ton code à 4 chiffres n&apos;est jamais stocké en clair : il est chiffré (hashé) dès la
          création de ton compte. Les échanges avec StreamMali passent par une connexion chiffrée
          (HTTPS).
        </p>
      </TermsSection>

      <TermsSection title="Cookies et stockage local">
        <p>
          StreamMali n&apos;utilise pas de cookies publicitaires ni de traceurs tiers. Le site
          garde localement, sur ton appareil, ton jeton de connexion et le fait que tu aies déjà vu
          le message de bienvenue — rien de plus, rien qui te suive d&apos;un site à l&apos;autre.
        </p>
      </TermsSection>

      <TermsSection title="Tes droits">
        <p>
          Tu peux demander à consulter, corriger, ou supprimer tes données personnelles à tout
          moment en contactant{" "}
          <a href="mailto:support@streammali.com" className="text-orange-600 hover:underline dark:text-orange-400">
            support@streammali.com
          </a>
          .
        </p>
      </TermsSection>

      <TermsSection title="Évolution de cette politique">
        <p>
          StreamMali peut modifier cette politique pour tenir compte de nouvelles fonctionnalités
          ou obligations légales. La version en vigueur est toujours celle publiée sur la
          plateforme.
        </p>
      </TermsSection>

      <TermsSection title="Contact">
        <p>
          Une question sur tes données ?{" "}
          <a href="mailto:support@streammali.com" className="text-orange-600 hover:underline dark:text-orange-400">
            support@streammali.com
          </a>
        </p>
      </TermsSection>
    </div>
  );
}
