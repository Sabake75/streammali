import Link from "next/link";
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "CGU spectateur — StreamMali",
  description: "Conditions générales d'utilisation de StreamMali pour les spectateurs.",
};

export default function TermsViewerPage() {
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
        Conditions générales d&apos;utilisation — Spectateur
      </h1>

      <div className="mt-8 flex flex-col gap-8">
        <Section title="Objet">
          <p>
            StreamMali est une plateforme malienne qui te permet de regarder des films, clips et
            sketchs de créateurs maliens, à l&apos;unité, sans abonnement. Ces Conditions Générales
            d&apos;Utilisation (CGU) expliquent les règles à respecter quand tu utilises StreamMali
            en tant que spectateur.
          </p>
          <p>En créant un compte, tu acceptes ces règles.</p>
        </Section>

        <Section title="Ton compte">
          <p>
            Tu t&apos;inscris avec ton numéro de téléphone et un code à 4 chiffres. Ce code te sert
            de mot de passe : garde-le secret et ne le partage avec personne.
          </p>
          <p>
            Les informations que tu donnes (nom, téléphone) doivent être exactes. Un seul compte
            par personne.
          </p>
        </Section>

        <Section title="Le catalogue et l'aperçu gratuit">
          <p>
            Tu peux parcourir librement le catalogue et regarder un aperçu gratuit de chaque vidéo
            avant de l&apos;acheter, pour savoir si elle te plaît.
          </p>
        </Section>

        <Section title="L'achat d'une vidéo">
          <p>
            Chaque vidéo coûte <strong>100 FCFA</strong>, prix fixe, à l&apos;unité. Il n&apos;y a
            pas d&apos;abonnement : tu ne payes que pour les vidéos que tu choisis.
          </p>
          <p>
            Le paiement se fait uniquement par <strong>Orange Money</strong> pour l&apos;instant.
            Une fois le paiement confirmé, tu as accès à la vidéo en streaming illimité, à tout
            moment.
          </p>
        </Section>

        <Section title="Ce que tu peux faire avec une vidéo achetée">
          <p>
            Une vidéo achetée est pour ton usage personnel : tu peux la regarder autant de fois que
            tu veux. Tu ne peux pas la télécharger, la copier, l&apos;enregistrer ou la partager
            avec d&apos;autres personnes (fichier, lien, capture d&apos;écran de qualité destinée à
            la revente, etc.). Le contenu appartient à son créateur.
          </p>
        </Section>

        <Section title="Remboursement">
          <p>
            Une fois une vidéo débloquée, l&apos;achat est définitif : il n&apos;y a pas de droit à
            changer d&apos;avis, comme pour tout contenu numérique consommé immédiatement.
          </p>
          <p>
            Un remboursement est possible uniquement si un problème technique confirmé
            t&apos;empêche complètement d&apos;accéder à la vidéo après un paiement bien débité.
            Contacte le support dans ce cas.
          </p>
        </Section>

        <Section title="Signaler un contenu">
          <p>
            Si une vidéo te semble illicite, copiée sans autorisation, ou pose problème d&apos;une
            autre façon, tu peux la signaler directement depuis sa fiche. Notre équipe de
            modération l&apos;examine.
          </p>
        </Section>

        <Section title="Suspension de compte">
          <p>
            StreamMali peut suspendre ou bloquer un compte en cas d&apos;usage frauduleux, abusif,
            ou de non-respect de ces CGU (partage de compte, tentative de piratage de contenu, faux
            signalements répétés, etc.).
          </p>
        </Section>

        <Section title="Tes données personnelles">
          <p>
            Nous collectons ton nom et ton numéro de téléphone pour créer et gérer ton compte, et
            pour le paiement Orange Money. Ces informations ne sont jamais revendues à des tiers.
            Elles servent uniquement au bon fonctionnement de StreamMali.
          </p>
        </Section>

        <Section title="Évolution de ces CGU">
          <p>
            StreamMali peut modifier ces CGU pour tenir compte de nouvelles fonctionnalités ou
            obligations légales. La version en vigueur est toujours celle publiée sur la
            plateforme.
          </p>
        </Section>

        <Section title="Droit applicable et contact">
          <p>
            Ces CGU sont soumises au droit malien. Pour toute question, écris-nous à{" "}
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
