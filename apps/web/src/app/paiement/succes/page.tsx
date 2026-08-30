import type { Metadata } from "next";
import { PaymentSuccessPageClient } from "./PaymentSuccessPageClient";

export const metadata: Metadata = {
  title: "Confirmation de paiement — StreamMali",
};

export default function PaymentSuccessPage() {
  return <PaymentSuccessPageClient />;
}
