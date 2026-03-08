interface DeleteMarkerResponse {
  message: string;
}

export function useMarkerDelete() {
  const { apiFetch } = useApi();

  async function deleteMarker(markerId: number): Promise<DeleteMarkerResponse> {
    return await apiFetch<DeleteMarkerResponse>(`/markers/${markerId}`, {
      method: 'DELETE'
    });
  }

  return { deleteMarker };
}
