import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Search } from 'lucide-react';
import AttractionCard from '../components/AttractionCard';
import EmptyState from '../components/EmptyState';
import { ErrorAlert } from '../components/Alert';
import { SkeletonCards } from '../components/LoadingState';
import { getAttractions } from '../api/attractionApi';
import { CATEGORIES, THRILL_LEVELS } from '../utils/status';

// Attractions grid with filters (design/Screens.md section 2, FR-V-03 to FR-V-05).
// Filters live in the URL (?category=water&thrill_level=high) and the API is called with the same values.
export default function AttractionsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const category = searchParams.get('category') || '';
  const thrillLevel = searchParams.get('thrill_level') || '';

  const [attractions, setAttractions] = useState([]);
  const [totalCount, setTotalCount] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [areFiltersOpen, setAreFiltersOpen] = useState(false);

  const activeFilterCount = (category ? 1 : 0) + (thrillLevel ? 1 : 0);

  function loadAttractions() {
    setIsLoading(true);
    setError(null);

    getAttractions({ category, thrill_level: thrillLevel })
      .then((result) => {
        setAttractions(result);
        // The first list without filters tells us how many rides there are in total.
        if (activeFilterCount === 0) setTotalCount(result.length);
      })
      .catch(setError)
      .finally(() => setIsLoading(false));
  }

  useEffect(loadAttractions, [category, thrillLevel]);

  useEffect(() => {
    if (totalCount === null && activeFilterCount > 0) {
      getAttractions().then((all) => setTotalCount(all.length)).catch(() => {});
    }
  }, []);

  function setFilter(name, value) {
    const nextParams = new URLSearchParams(searchParams);
    if (value) nextParams.set(name, value);
    else nextParams.delete(name);
    setSearchParams(nextParams);
  }

  function clearFilters() {
    setSearchParams(new URLSearchParams());
  }

  return (
    <div className="wl-container wl-stack">
      <div>
        <h1>Attractions</h1>
        <p className="wl-muted">{totalCount ?? 8} rides and activities. Check age and height before you go.</p>
      </div>

      <div className={`wl-filter-bar${areFiltersOpen ? '' : ' is-collapsed'}`}>
        <button type="button" className="wl-btn wl-filter-toggle" aria-expanded={areFiltersOpen} onClick={() => setAreFiltersOpen(!areFiltersOpen)}>
          <Search aria-hidden="true" />
          Filters{activeFilterCount > 0 ? ` (${activeFilterCount})` : ''}
        </button>
        <div className="wl-filter-bar__rows">
          <ChipRow label="Category" options={CATEGORIES} value={category} onChange={(value) => setFilter('category', value)} />
          <ChipRow label="Thrill level" options={THRILL_LEVELS} value={thrillLevel} onChange={(value) => setFilter('thrill_level', value)} />
        </div>
        <div className="wl-filter-bar__foot">
          <span className="wl-muted" aria-live="polite">
            {!isLoading && !error && `Showing ${attractions.length} of ${totalCount ?? attractions.length}`}
          </span>
          {activeFilterCount > 0 && <button type="button" className="wl-btn wl-btn--ghost" onClick={clearFilters}>Clear filters</button>}
        </div>
      </div>

      {error && <ErrorAlert error={error} onRetry={loadAttractions} />}
      {isLoading && <SkeletonCards count={8} label="Loading attractions…" />}
      {!isLoading && !error && attractions.length === 0 && (
        <EmptyState
          icon={Search}
          title="No rides match these filters"
          text="Try another category or thrill level."
          action={<button type="button" className="wl-btn wl-btn--primary" onClick={clearFilters}>Clear filters</button>}
        />
      )}
      {!isLoading && !error && attractions.length > 0 && (
        <div className="wl-grid wl-grid--4">
          {attractions.map((attraction) => <AttractionCard key={attraction.id} attraction={attraction} />)}
        </div>
      )}
    </div>
  );
}

// One row of filter chips with an "All" chip. One choice per row, because the API takes one value.
function ChipRow({ label, options, value, onChange }) {
  return (
    <div className="wl-stack" style={{ gap: 'var(--space-2)' }}>
      <span className="wl-label">{label}</span>
      <div className="wl-row" role="group" aria-label={label}>
        <button type="button" className="wl-chip" aria-pressed={value === ''} onClick={() => onChange('')}>All</button>
        {options.map((option) => (
          <button key={option.value} type="button" className="wl-chip" aria-pressed={value === option.value} onClick={() => onChange(option.value)}>
            {option.label}
          </button>
        ))}
      </div>
    </div>
  );
}
