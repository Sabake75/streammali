import Link from "next/link";
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "CGU créateur — StreamMali",
  description: "Conditions générales d'utilisation de StreamMali pour les créateurs, dont la répartition des revenus.",
};

export default function TermsCreatorPage() {
  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <Link
        href="/"
        className="text-sm text-neutral-500 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        ← Retour au catalogue
      </Link>

      <h1 className="mt-4 flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Conditions générales d&apos;utilisation — Créateur
      </h1>

      <div className="mt-8 flex flex-col gap-8">
        <Section title="Objet">
          <p>
            Ces Conditions Générales d&apos;Utilisation (CGU) s&apos;appliquent à toute personne
            qui s&apos;inscrit sur StreamMali en tant que <strong>créateur</strong> : réalisateur,
            artiste, humoriste ou tout producteur de contenu qui souhaite publier des vidéos
            (films, clips, sketchs, web-séries) sur la plateforme et être payé lorsqu&apos;un
            spectateur les regarde.
          </p>
          <p>En créant un compte créateur, tu acceptes les règles décrites ci-dessous.</p>
        </Section>

        <Section title="Inscription créateur">
          <p>Pour t&apos;inscrire comme créateur, tu dois fournir :</p>
          <ul className="list-disc pl-5">
            <li>ton numéro de téléphone,</li>
            <li>un code à 4 chiffres qui te servira de mot de passe,</li>
            <li>une pièce d&apos;identité, pour vérifier que tu es bien la personne que tu déclares être.</li>
          </ul>
          <p>
            Les informations que tu donnes doivent être exactes. Si StreamMali a un doute sur ton
            identité, ton compte peut être bloqué le temps de vérifier.
          </p>
        </Section>

        <Section title="Propriété et droits sur les contenus">
          <p>
            Chaque vidéo que tu publies doit t&apos;appartenir, ou tu dois avoir l&apos;autorisation
            de son auteur pour la mettre en ligne et la vendre.
          </p>
          <p>
            En soumettant une vidéo, tu déclares sur l&apos;honneur que tu as bien ce droit. Si une
            vidéo est en réalité volée, copiée ou publiée sans autorisation,{" "}
            <strong>tu es seul responsable</strong> : StreamMali peut la retirer immédiatement et
            bloquer ton compte.
          </p>
        </Section>

        <Section title="Soumission et modération">
          <p>Aucune vidéo n&apos;est visible du public tant qu&apos;elle n&apos;a pas été validée par la modération.</p>
          <p>Après ta soumission :</p>
          <ul className="list-disc pl-5">
            <li>un modérateur regarde la vidéo,</li>
            <li>
              il la <strong>valide</strong> (elle devient visible dans le catalogue) ou la{" "}
              <strong>refuse</strong> (avec un motif expliqué),
            </li>
            <li>si elle est refusée, tu peux corriger le problème et la soumettre à nouveau.</li>
          </ul>
        </Section>

        <Section title="Prix de vente">
          <p>
            Le prix par défaut d&apos;une vidéo est de <strong>100 FCFA</strong>. Tu peux le
            confirmer ou l&apos;ajuster, dans la limite de ce que StreamMali autorise.
          </p>
        </Section>

        <Section title="Répartition des revenus">
          <p>C&apos;est la règle la plus importante à retenir :</p>
          <p className="font-semibold text-neutral-900 dark:text-neutral-50">
            Sur chaque vente, StreamMali garde 25 % et tu reçois 75 %.
          </p>
          <p>Exemple concret avec le prix par défaut de 100 FCFA :</p>
          <div className="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-800">
            <table className="w-full text-left text-sm">
              <tbody>
                <tr className="border-b border-neutral-200 dark:border-neutral-800">
                  <td className="px-4 py-2">Prix payé par le spectateur</td>
                  <td className="px-4 py-2 font-medium">100 FCFA</td>
                </tr>
                <tr className="border-b border-neutral-200 dark:border-neutral-800">
                  <td className="px-4 py-2">Ta part (créateur)</td>
                  <td className="px-4 py-2 font-bold text-orange-700 dark:text-orange-400">75 FCFA</td>
                </tr>
                <tr>
                  <td className="px-4 py-2">Part StreamMali (commission)</td>
                  <td className="px-4 py-2 font-medium">25 FCFA</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p>
            Cette répartition est la même quel que soit le prix de la vidéo : tu gardes toujours
            75 % de chaque vente.
          </p>
        </Section>

        <Section title="Reversement de tes revenus">
          <p>Tes gains s&apos;accumulent sur ton solde StreamMali. Pour les récupérer :</p>
          <ul className="list-disc pl-5">
            <li>tu fais une <strong>demande de retrait</strong> vers ton compte Mobile Money,</li>
            <li>le montant minimum pour retirer est de <strong>10 000 FCFA</strong>,</li>
            <li>les demandes sont traitées <strong>chaque semaine</strong>,</li>
            <li>
              les frais prélevés par les opérateurs Mobile Money sont{" "}
              <strong>pris en charge par StreamMali</strong> — tu ne payes aucun frais caché.
            </li>
          </ul>
        </Section>

        <Section title="Dépublication et suspension">
          <p>StreamMali peut :</p>
          <ul className="list-disc pl-5">
            <li>
              <strong>dépublier une vidéo</strong> si elle est signalée à juste titre (contenu
              illicite, volé, ou qui ne respecte pas ces CGU),
            </li>
            <li>
              <strong>suspendre ton compte</strong> en cas de fraude, de contenu illégal, ou si tu
              publies plusieurs fois des vidéos qui ne t&apos;appartiennent pas.
            </li>
          </ul>
        </Section>

        <Section title="Résiliation">
          <p>
            Tu peux demander la fermeture de ton compte créateur à tout moment. Les vidéos déjà
            achetées par des spectateurs restent accessibles à ces spectateurs, même après la
            fermeture de ton compte.
          </p>
        </Section>

        <Section title="Données personnelles">
          <p>
            StreamMali collecte ton numéro de téléphone, ton nom et ta pièce d&apos;identité. Ces
            informations servent uniquement à :
          </p>
          <ul className="list-disc pl-5">
            <li>gérer ton compte,</li>
            <li>vérifier ton identité,</li>
            <li>te reverser tes revenus.</li>
          </ul>
          <p>Ces données ne sont jamais revendues à des tiers.</p>
        </Section>

        <Section title="Modification des CGU">
          <p>
            StreamMali peut faire évoluer ces CGU. La version qui s&apos;applique est toujours
            celle publiée sur la plateforme au moment où tu l&apos;utilises.
          </p>
        </Section>

        <Section title="Droit applicable">
          <p>
            Ces CGU sont soumises au droit malien. Pour toute question, contacte-nous à{" "}
            <a href="mailto:support@streammali.com" className="text-orange-600 hover:underline dark:text-orange-400">
              support@streammali.com
            </a>
            .
          </p>
        </Section>
      </div>
    </main>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section>
      <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{title}</h2>
      <div className="mt-2 flex flex-col gap-2 text-neutral-700 dark:text-neutral-300">
        {children}
      </div>
    </section>
  );
}
