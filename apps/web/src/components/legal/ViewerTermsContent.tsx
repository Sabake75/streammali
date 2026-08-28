import { TermsSection } from "./TermsSection";

export function ViewerTermsContent() {
  return (
    <div className="flex flex-col gap-8">
      <TermsSection title="Objet">
        <p>
          StreamMali est une plateforme malienne qui te permet de regarder des films, clips et
          sketchs de créateurs maliens, à l&apos;unité, sans abonnement. Ces Conditions Générales
          d&apos;Utilisation (CGU) expliquent les règles à respecter quand tu utilises StreamMali
          en tant que spectateur.
        </p>
        <p>En créant un compte, tu acceptes ces règles.</p>
      </TermsSection>

      <TermsSection title="Ton compte">
        <p>
          Tu t&apos;inscris avec ton numéro de téléphone et un code à 4 chiffres. Ce code te sert
          de mot de passe : garde-le secret et ne le partage avec personne.
        </p>
        <p>
          Les informations que tu donnes (nom, téléphone) doivent être exactes. Un seul compte par
          personne.
        </p>
      </TermsSection>

      <TermsSection title="Le catalogue et l'aperçu gratuit">
        <p>
          Tu peux parcourir librement le catalogue et regarder un aperçu gratuit de chaque vidéo
          avant de l&apos;acheter, pour savoir si elle te plaît.
        </p>
      </TermsSection>

      <TermsSection title="L'achat d'une vidéo">
        <p>
          Chaque vidéo est vendue à l&apos;unité, à un prix fixé par son créateur
          (<strong>100 FCFA par défaut</strong>, un créateur peut choisir un tarif différent). Il
          n&apos;y a pas d&apos;abonnement : tu ne payes que pour les vidéos que tu choisis, au
          prix affiché sur la fiche de chaque vidéo.
        </p>
        <p>
          Le paiement se fait par <strong>Mobile Money</strong> (Orange Money, Moov Money…).
          Une fois le paiement confirmé, tu as accès à la vidéo en streaming illimité, à tout
          moment.
        </p>
      </TermsSection>

      <TermsSection title="Ce que tu peux faire avec une vidéo achetée">
        <p>
          Une vidéo achetée est pour ton usage personnel : tu peux la regarder autant de fois que
          tu veux. Tu ne peux pas la télécharger, la copier, l&apos;enregistrer ou la partager avec
          d&apos;autres personnes (fichier, lien, capture d&apos;écran de qualité destinée à la
          revente, etc.). Le contenu appartient à son créateur.
        </p>
      </TermsSection>

      <TermsSection title="Remboursement">
        <p>
          Une fois une vidéo débloquée, l&apos;achat est définitif : il n&apos;y a pas de droit à
          changer d&apos;avis, comme pour tout contenu numérique consommé immédiatement.
        </p>
        <p>
          Un remboursement est possible uniquement si un problème technique confirmé
          t&apos;empêche complètement d&apos;accéder à la vidéo après un paiement bien débité.
          Contacte le support dans ce cas.
        </p>
      </TermsSection>

      <TermsSection title="Signaler un contenu">
        <p>
          Si une vidéo te semble illicite, copiée sans autorisation, ou pose problème d&apos;une
          autre façon, tu peux la signaler directement depuis sa fiche. Notre équipe de modération
          l&apos;examine.
        </p>
      </TermsSection>

      <TermsSection title="Suspension de compte">
        <p>
          StreamMali peut suspendre ou bloquer un compte en cas d&apos;usage frauduleux, abusif, ou
          de non-respect de ces CGU (partage de compte, tentative de piratage de contenu, faux
          signalements répétés, etc.).
        </p>
      </TermsSection>

      <TermsSection title="Tes données personnelles">
        <p>
          Nous collectons ton nom et ton numéro de téléphone pour créer et gérer ton compte, et
          pour le paiement Mobile Money. Ces informations ne sont jamais revendues à des tiers.
          Elles servent uniquement au bon fonctionnement de StreamMali.
        </p>
      </TermsSection>

      <TermsSection title="Évolution de ces CGU">
        <p>
          StreamMali peut modifier ces CGU pour tenir compte de nouvelles fonctionnalités ou
          obligations légales. La version en vigueur est toujours celle publiée sur la plateforme.
        </p>
      </TermsSection>

      <TermsSection title="Droit applicable et contact">
        <p>
          Ces CGU sont soumises au droit malien. Pour toute question, écris-nous à{" "}
          <a href="mailto:support@streammali.com" className="text-orange-600 hover:underline dark:text-orange-400">
            support@streammali.com
          </a>
          .
        </p>
      </TermsSection>
    </div>
  );
}
