export function FormField({
  id,
  label,
  type,
  value,
  onChange,
}: {
  id: string;
  label: string;
  type: string;
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="text-sm text-neutral-600 dark:text-neutral-400">
        {label}
      </label>
      <input
        id={id}
        type={type}
        required
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="rounded border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900"
      />
    </div>
  );
}
