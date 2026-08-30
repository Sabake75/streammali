import type { Metadata } from "next";
import { PaymentCancelledPageClient } from "./PaymentCancelledPageClient";

export const metadata: Metadata = {
  title: "Paiement annulé — StreamMali",
};

export default function PaymentCancelledPage() {
  return <PaymentCancelledPageClient />;
}
