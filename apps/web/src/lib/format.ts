export function formatDuration(seconds: number | null): string {
  if (!seconds) return "Durée inconnue";

  const hours = Math.floor(seconds / 3600);
  const minutes = Math.round((seconds % 3600) / 60);

  if (hours === 0) return `${minutes} min`;
  return `${hours} h ${minutes.toString().padStart(2, "0")}`;
}

export function formatPrice(price: number): string {
  return `${price} FCFA`;
}
