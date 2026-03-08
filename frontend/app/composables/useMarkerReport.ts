interface ReportMarkerPayload {
  reason: string;
  details?: string;
}

interface ReportMarkerResponse {
  message: string;
  data: {
    id: number;
    marker_id: number;
    reason: string;
  };
}

export function useMarkerReport() {
  const { apiFetch } = useApi();

  async function reportMarker(
    markerId: number,
    payload: ReportMarkerPayload
  ): Promise<ReportMarkerResponse> {
    return await apiFetch<ReportMarkerResponse>(`/markers/${markerId}/report`, {
      method: 'POST',
      body: payload
    });
  }

  return { reportMarker };
}
