import type { Marker } from '~/composables/useMarkers';

export type VoteType = 'still_there' | 'not_there';

interface VoteResponse {
  data: Marker;
  user_vote_type: VoteType | null;
  message: string;
}

export function useMarkerVote() {
  const { apiFetch } = useApi();

  async function vote(
    markerId: number,
    voteType: VoteType
  ): Promise<VoteResponse> {
    return await apiFetch<VoteResponse>(`/markers/${markerId}/vote`, {
      method: 'POST',
      body: { vote_type: voteType }
    });
  }

  return { vote };
}
