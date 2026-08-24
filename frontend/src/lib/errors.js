/**
 * Normalizes an Axios error from the Laravel API into a flat message plus
 * a field-keyed validation error bag, so forms can render both easily.
 */
export function parseApiError(error) {
  const response = error?.response;
  const data = response?.data;

  return {
    status: response?.status ?? null,
    message: data?.message || "Si è verificato un errore imprevisto. Riprova.",
    errors: data?.errors ?? {},
  };
}

export function firstError(errors, field) {
  return errors?.[field]?.[0] ?? null;
}
