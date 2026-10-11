import { queryString, request } from './client';

// pricing-service calls (design 6.2).

// includeInactive works only for admins.
export function getTiers({ includeInactive = false } = {}) {
  const query = queryString({ include_inactive: includeInactive ? 1 : '' });

  return request(`/api/tiers${query}`, { auth: includeInactive });
}

export function createTier(tier) {
  return request('/api/tiers', { method: 'POST', body: tier, auth: true });
}

export function updateTier(tierId, tier) {
  return request(`/api/tiers/${tierId}`, { method: 'PUT', body: tier, auth: true });
}
