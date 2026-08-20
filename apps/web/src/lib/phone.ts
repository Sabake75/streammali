import {
  getCountries,
  getCountryCallingCode,
  getExampleNumber,
  isValidPhoneNumber,
  type CountryCode as LibCountryCode,
} from "libphonenumber-js";
import examples from "libphonenumber-js/examples.mobile.json";

export type CountryCode = LibCountryCode;

const DISPLAY_NAMES = new Intl.DisplayNames(["fr"], { type: "region" });

export function countryName(country: CountryCode): string {
  return DISPLAY_NAMES.of(country) ?? country;
}

export const COUNTRIES: CountryCode[] = getCountries().sort((a, b) =>
  countryName(a).localeCompare(countryName(b), "fr"),
);

export const DEFAULT_COUNTRY: CountryCode = "ML";

/** Expected national number length for this country, from libphonenumber's own data — never guessed. */
export function maxDigitsFor(country: CountryCode): number {
  return getExampleNumber(country, examples)?.nationalNumber.length ?? 15;
}

export function isValidPhone(phone: string): boolean {
  return isValidPhoneNumber(phone);
}

export function composePhone(country: CountryCode, digits: string): string {
  return `+${getCountryCallingCode(country)}${digits}`;
}

/** Best-effort split of a full phone string (e.g. from a prefilled value) into country + digits. */
export function splitPhone(phone: string): { country: CountryCode; digits: string } {
  const match = COUNTRIES
    .map((country) => ({ country, code: getCountryCallingCode(country) }))
    .filter(({ code }) => phone.startsWith(`+${code}`))
    .sort((a, b) => b.code.length - a.code.length)[0];

  if (match) {
    return { country: match.country, digits: phone.slice(match.code.length + 1) };
  }

  return { country: DEFAULT_COUNTRY, digits: phone.replace(/\D/g, "") };
}

export { getCountryCallingCode };
