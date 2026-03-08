export interface Marker {
  id: number;
  latitude: string;
  longitude: string;
  address?: string | null;
  category: string;
  description: string;
  likes: number;
  dislikes: number;
  images: Array<{ id: number; image_url: string }>;
  user: { id: number; name: string };
  user_vote_type?: 'still_there' | 'not_there' | null;
  created_at: string;
  updated_at: string;
}

export interface BoundsParams {
  latitude: number;
  longitude: number;
  radius: number;
}

export function boundsToParams(bounds: {
  getCenter: () => { lat: number; lng: number };
  getNorthEast: () => { lat: number; lng: number };
}): BoundsParams {
  const center = bounds.getCenter();
  const ne = bounds.getNorthEast();
  const kmPerDegLat = 111;
  const kmPerDegLng = 111 * Math.cos((center.lat * Math.PI) / 180);
  const radiusKm = Math.sqrt(
    Math.pow((ne.lat - center.lat) * kmPerDegLat, 2) +
      Math.pow((ne.lng - center.lng) * kmPerDegLng, 2)
  );
  return {
    latitude: center.lat,
    longitude: center.lng,
    radius: Math.max(0.1, Math.min(1000, radiusKm))
  };
}

export async function fetchMarkers(
  params: BoundsParams,
  selectedDate: string,
  selectedCategory: string
): Promise<Marker[]> {
  const { apiFetch } = useApi();

  const query: Record<string, string | number> = {
    latitude: params.latitude,
    longitude: params.longitude,
    radius: params.radius
  };
  if (selectedDate) {
    query.start_date = selectedDate;
    query.end_date = selectedDate;
  }
  if (selectedCategory && selectedCategory !== 'all') {
    query.category = selectedCategory;
  }

  const response = await apiFetch<{ data: Marker[] }>('/markers', {
    query
  });
  return response.data ?? [];
}

export function formatDateForInput(d: Date): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export const CATEGORY_OPTIONS = [
  { value: 'all', label: 'All' },
  { value: 'checkpoint', label: 'Checkpoint' },
  { value: 'road_repair', label: 'Road Repair' },
  { value: 'accident', label: 'Accident' },
  { value: 'traffic', label: 'Traffic' },
  { value: 'flood', label: 'Flood' },
  { value: 'hazard', label: 'Hazard' }
];

export const CATEGORY_COLORS: Record<string, string> = {
  accident: '#DC2626',
  hazard: '#F97316',
  road_repair: '#F59E0B',
  traffic: '#EAB308',
  flood: '#0EA5E9',
  checkpoint: '#059212'
};

export function getCategoryColor(category: string): string {
  return CATEGORY_COLORS[category] ?? '#059212';
}

export function formatCategoryLabel(category: string): string {
  return category
    .replace(/[_-]+/g, ' ')
    .trim()
    .split(/\s+/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ');
}
