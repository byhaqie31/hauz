/**
 * Maps a Laravel 422 validation error (`{ message, errors: { field: string[] } }`,
 * surfaced by ofetch as `FetchError.data`) into vee-validate's `setErrors` shape
 * (`{ field: firstMessage }`). Returns null for any non-422 / unshaped error so
 * callers can fall back to a generic toast.
 */
export const useApiError = () => {
  const toFieldErrors = (error: unknown): Record<string, string> | null => {
    const data = (error as { data?: unknown })?.data as
      | { errors?: Record<string, string[]> }
      | undefined;
    if (!data?.errors || typeof data.errors !== "object") return null;

    const out: Record<string, string> = {};
    for (const [field, messages] of Object.entries(data.errors)) {
      if (Array.isArray(messages) && messages.length > 0 && messages[0]) {
        out[field] = messages[0];
      }
    }
    return Object.keys(out).length > 0 ? out : null;
  };

  return { toFieldErrors };
};
