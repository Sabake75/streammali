"use client";

import { useState } from "react";
import {
  COUNTRIES,
  DEFAULT_COUNTRY,
  composePhone,
  countryName,
  getCountryCallingCode,
  maxDigitsFor,
  splitPhone,
  type CountryCode,
} from "@/lib/phone";

export function PhoneNumberField({
  id,
  label = "Téléphone",
  value,
  onChange,
}: {
  id: string;
  label?: string;
  /** Full phone string (e.g. "+22376123456"), same shape passed to onChange. */
  value: string;
  onChange: (phone: string) => void;
}) {
  const [country, setCountry] = useState<CountryCode>(
    () => (value ? splitPhone(value) : { country: DEFAULT_COUNTRY, digits: "" }).country,
  );
  const [digits, setDigits] = useState<string>(
    () => (value ? splitPhone(value) : { country: DEFAULT_COUNTRY, digits: "" }).digits,
  );
  // Tracks the last external `value` we synced from, so a prop change (e.g. the
  // logged-in user's phone loading in asynchronously) can be picked up during
  // render rather than in an effect — see react.dev "Adjusting state on a prop
  // change" — while our own edits (which also change `value` via onChange)
  // don't get clobbered by re-deriving from a value we ourselves just produced.
  const [lastValue, setLastValue] = useState(value);
  if (value !== lastValue) {
    setLastValue(value);
    const split = value ? splitPhone(value) : { country: DEFAULT_COUNTRY, digits: "" };
    setCountry(split.country);
    setDigits(split.digits);
  }

  const maxDigits = maxDigitsFor(country);

  function handleDigitsChange(raw: string) {
    const cleaned = raw.replace(/\D/g, "").slice(0, maxDigits);
    setDigits(cleaned);
    onChange(composePhone(country, cleaned));
  }

  function handleCountryChange(next: CountryCode) {
    setCountry(next);
    const cleaned = digits.slice(0, maxDigitsFor(next));
    setDigits(cleaned);
    onChange(composePhone(next, cleaned));
  }

  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="text-sm text-neutral-600 dark:text-neutral-400">
        {label}
      </label>
      <div className="flex gap-2">
        <select
          aria-label="Indicatif pays"
          value={country}
          onChange={(event) => handleCountryChange(event.target.value as CountryCode)}
          className="input-field px-2"
        >
          {COUNTRIES.map((c) => (
            <option key={c} value={c}>
              {countryName(c)} (+{getCountryCallingCode(c)})
            </option>
          ))}
        </select>
        <input
          id={id}
          type="tel"
          inputMode="numeric"
          required
          value={digits}
          onChange={(event) => handleDigitsChange(event.target.value)}
          maxLength={maxDigits}
          placeholder={"X".repeat(maxDigits)}
          className="input-field flex-1"
        />
      </div>
    </div>
  );
}
