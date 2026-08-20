"use client";

import { useCallback, useEffect, useState } from "react";
import { fetchMyMessages, sendMessage } from "@/lib/api-client";
import type { Message } from "@/lib/types";

export function Messaging() {
  const [messages, setMessages] = useState<Message[] | null>(null);
  const [body, setBody] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchMyMessages()
      .then((response) => setMessages(response.data))
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await sendMessage(body);
      setBody("");
      reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
      <h2 className="font-semibold text-neutral-900 dark:text-neutral-50">Messagerie avec la modération</h2>

      <div className="mt-3 flex max-h-80 flex-col gap-3 overflow-y-auto">
        {messages === null && <p className="text-neutral-500">Chargement…</p>}
        {messages?.length === 0 && (
          <p className="text-sm text-neutral-500 dark:text-neutral-400">
            Aucun message pour l&apos;instant. Pose une question à la modération ci-dessous.
          </p>
        )}
        {messages?.map((message) => {
          const isModerator = message.sender.role === "moderator";
          return (
            <div
              key={message.id}
              className={`flex flex-col gap-0.5 rounded-lg px-3 py-2 text-sm ${
                isModerator
                  ? "self-start bg-neutral-100 dark:bg-neutral-800"
                  : "self-end bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900"
              }`}
            >
              <span className="text-xs font-medium opacity-70">
                {isModerator ? "Modération" : message.sender.name}
              </span>
              <span className="whitespace-pre-wrap">{message.body}</span>
            </div>
          );
        })}
      </div>

      <form onSubmit={handleSubmit} className="mt-4 flex flex-col gap-2">
        <textarea
          id="message-body"
          required
          rows={2}
          value={body}
          onChange={(event) => setBody(event.target.value)}
          placeholder="Écris ton message à la modération…"
          className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        />
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <button
          type="submit"
          disabled={submitting}
          className="w-fit rounded bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 disabled:opacity-60 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
        >
          {submitting ? "Envoi…" : "Envoyer"}
        </button>
      </form>
    </div>
  );
}
