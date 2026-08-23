export function TermsSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section>
      <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{title}</h2>
      <div className="mt-2 flex flex-col gap-2 text-neutral-700 dark:text-neutral-300">
        {children}
      </div>
    </section>
  );
}
