import { useEffect, useState } from 'react';
import { Plus, Search } from 'lucide-react';
import AttractionForm from './AttractionForm';
import StatusBadge from '../../components/StatusBadge';
import ThrillMeter from '../../components/ThrillMeter';
import Dialog from '../../components/Dialog';
import EmptyState from '../../components/EmptyState';
import Alert, { ErrorAlert } from '../../components/Alert';
import { SkeletonRows } from '../../components/LoadingState';
import { activateAttraction, createAttraction, getAttractions, retireAttraction, updateAttraction } from '../../api/attractionApi';
import { categoryLabel } from '../../utils/status';

// Create, edit, retire and activate attractions (design/Screens.md section 9, FR-A-01 to FR-A-03).
export default function ManageAttractionsPage() {
  const [attractions, setAttractions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchText, setSearchText] = useState('');
  const [showRetired, setShowRetired] = useState(false);
  const [editing, setEditing] = useState(null);
  const [retiring, setRetiring] = useState(null);
  const [isRetiring, setIsRetiring] = useState(false);
  const [message, setMessage] = useState(null);

  // We always load retired ones too (admin only) and filter in the browser with "Show retired".
  function loadAttractions() {
    setIsLoading(true);
    setError(null);
    getAttractions({}, { includeRetired: true })
      .then(setAttractions)
      .catch(setError)
      .finally(() => setIsLoading(false));
  }

  useEffect(loadAttractions, []);

  async function handleSave(values) {
    const saved = editing.isNew ? await createAttraction(values) : await updateAttraction(editing.attraction.id, values);
    setEditing(null);
    setMessage({ variant: 'success', text: `${saved.name} saved.` });
    loadAttractions();
  }

  async function handleRetireConfirm() {
    setIsRetiring(true);
    try {
      await retireAttraction(retiring.id);
      setMessage({ variant: 'success', text: `${retiring.name} retired. It's hidden from visitors.` });
      loadAttractions();
    } catch (retireError) {
      setMessage({ variant: 'danger', text: retireError.message });
    } finally {
      setIsRetiring(false);
      setRetiring(null);
    }
  }

  async function handleActivate(attraction) {
    try {
      await activateAttraction(attraction.id);
      setMessage({ variant: 'success', text: `${attraction.name} is active again.` });
      loadAttractions();
    } catch (activateError) {
      setMessage({ variant: 'danger', text: activateError.message });
    }
  }

  const visibleAttractions = attractions
    .filter((attraction) => showRetired || attraction.status === 'active')
    .filter((attraction) => attraction.name.toLowerCase().includes(searchText.trim().toLowerCase()));

  return (
    <div className="wl-stack">
      <div className="wl-page-head">
        <h1>Attractions</h1>
        <button type="button" className="wl-btn wl-btn--primary" onClick={() => setEditing({ isNew: true })}>
          <Plus aria-hidden="true" />
          Add attraction
        </button>
      </div>

      {message && <Alert variant={message.variant} title={message.text} />}

      <div className="wl-table-tools">
        <div className="wl-field">
          <label className="wl-sr-only" htmlFor="attraction-search">Search by name</label>
          <div className="wl-input-icon">
            <Search aria-hidden="true" />
            <input id="attraction-search" className="wl-input" placeholder="Search by name" value={searchText} onChange={(event) => setSearchText(event.target.value)} />
          </div>
        </div>
        <label className="wl-check">
          <input type="checkbox" checked={showRetired} onChange={(event) => setShowRetired(event.target.checked)} />
          Show retired
        </label>
      </div>

      {isLoading && <SkeletonRows count={8} label="Loading attractions…" />}
      {error && <ErrorAlert error={error} onRetry={loadAttractions} />}
      {!isLoading && !error && visibleAttractions.length === 0 && <EmptyState icon={Search} title="No attractions found" text="Try another name or show retired ones." />}
      {!isLoading && !error && visibleAttractions.length > 0 && (
        <div className="wl-table-wrap">
          <table className="wl-table wl-table--stack">
            <thead>
              <tr>
                <th>Name</th><th>Category</th><th>Thrill</th><th className="is-num">Min age</th><th className="is-num">Min height</th><th>Status</th>
                <th><span className="wl-sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              {visibleAttractions.map((attraction) => (
                <tr key={attraction.id}>
                  <td data-label="Name"><strong>{attraction.name}</strong></td>
                  <td data-label="Category"><span className={`wl-badge wl-badge--${attraction.category}`}>{categoryLabel(attraction.category)}</span></td>
                  <td data-label="Thrill"><ThrillMeter level={attraction.thrill_level} /></td>
                  <td data-label="Min age" className="is-num">{attraction.min_age > 0 ? `${attraction.min_age}+` : 'All ages'}</td>
                  <td data-label="Min height" className="is-num">{attraction.min_height_cm > 0 ? `${attraction.min_height_cm} cm` : '–'}</td>
                  <td data-label="Status"><StatusBadge status={attraction.status} /></td>
                  <td className="wl-actions">
                    <button type="button" className="wl-btn wl-btn--sm wl-btn--ghost" onClick={() => setEditing({ attraction })}>Edit</button>
                    {attraction.status === 'active' ? (
                      <button type="button" className="wl-btn wl-btn--sm" onClick={() => setRetiring(attraction)}>Retire</button>
                    ) : (
                      <button type="button" className="wl-btn wl-btn--sm wl-btn--ghost" onClick={() => handleActivate(attraction)}>Activate</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {editing && <AttractionForm attraction={editing.attraction} onSave={handleSave} onCancel={() => setEditing(null)} />}

      <Dialog
        isOpen={retiring !== null}
        title={`Retire ${retiring?.name}?`}
        confirmLabel="Retire attraction"
        isBusy={isRetiring}
        onConfirm={handleRetireConfirm}
        onCancel={() => setRetiring(null)}
      >
        Visitors won&apos;t see it any more. Past ride scans stay in reports, and you can make it active again later.
      </Dialog>
    </div>
  );
}
