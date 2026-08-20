import {
  getCountryCallingCode,
  getExampleNumber,
  isValidPhoneNumber,
  type CountryCode as LibCountryCode,
} from "libphonenumber-js";
import examples from "libphonenumber-js/examples.mobile.json";
import { FR_COUNTRY_NAMES } from "./phone-country-names";

export type CountryCode = LibCountryCode;

// French country names are baked in ahead of time (generated from Node's ICU
// data, see phone-country-names.ts) rather than resolved at runtime via
// Intl.DisplayNames: Node and Chrome ship different ICU data (e.g. "Hong
// Kong" vs "R.A.S. chinoise de Hong Kong"), so calling it separately during
// SSR and client hydration produced different text — and therefore a
// different sort order — causing a React hydration mismatch. Static data is
// identical in both environments by construction.
export function countryName(country: CountryCode): string {
  return FR_COUNTRY_NAMES[country] ?? country;
}

export const COUNTRIES: CountryCode[] = Object.keys(FR_COUNTRY_NAMES) as CountryCode[];

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
