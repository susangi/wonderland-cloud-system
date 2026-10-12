import { useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import Dialog from '../../components/Dialog';
import Alert, { ErrorAlert } from '../../components/Alert';
import { SkeletonRows } from '../../components/LoadingState';
import { createTier, getTiers, updateTier } from '../../api/pricingApi';

// Editable tier table (design/Screens.md section 10, FR-A-04, FR-A-05).
// Name, guests and price are saved with "Save changes". The Active toggle saves at once (design Controls),
// and turning a tier off asks first, because visitors stop seeing it.

let newRowNumber = 0;

function toRow(tier) {
  return { ...tier, key: `tier-${tier.id}`, isNew: false, isDirty: false, state: null };
}

// Same rules as pricing-service: min ≥ 1, max ≥ min, max ≤ 10, price > 0.
function validateRow(row) {
  if (row.isNew && !row.code.trim()) return 'Enter a code, for example SCHOOL.';
  if (!row.name.trim()) return 'Enter a name.';
  if (Number(row.min_guests) < 1) return 'Min guests must be at least 1.';
  if (Number(row.max_guests) < Number(row.min_guests)) return 'Max guests must be at least min guests.';
  if (Number(row.max_guests) > 10) return 'Max 10 guests.';
  if (!(Number(row.price) > 0)) return 'Price must be more than 0.';
  return null;
}

function tierPayload(row, isActive = row.is_active) {
  return { name: row.name, min_guests: Number(row.min_guests), max_guests: Number(row.max_guests), price: Number(row.price), is_active: isActive };
}

export default function ManagePricingPage() {
  const [rows, setRows] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isSaving, setIsSaving] = useState(false);
  const [deactivating, setDeactivating] = useState(null);

  function loadTiers() {
    setIsLoading(true);
    setError(null);
    getTiers({ includeInactive: true })
      .then((tiers) => setRows(tiers.map(toRow)))
      .catch(setError)
      .finally(() => setIsLoading(false));
  }

  useEffect(loadTiers, []);

  function updateRow(key, changes) {
    setRows((current) => current.map((row) => (row.key === key ? { ...row, ...changes } : row)));
  }

  function handleAddTier() {
    newRowNumber += 1;
    const newRow = { key: `new-${newRowNumber}`, isNew: true, isDirty: true, state: null, code: '', name: '', min_guests: 1, max_guests: 1, price: '', currency: 'USD', is_active: true };
    setRows([newRow, ...rows]);
  }

  // Saves every changed row, one by one, and marks each row "Saved" or with its error.
  async function handleSaveChanges() {
    setIsSaving(true);

    for (const row of rows.filter((item) => item.isDirty)) {
      const problem = validateRow(row);
      if (problem) {
        updateRow(row.key, { state: { error: problem } });
        continue;
      }

      try {
        const saved = row.isNew
          ? await createTier({ code: row.code, ...tierPayload(row), currency: 'USD' })
          : await updateTier(row.id, tierPayload(row));
        updateRow(row.key, { ...toRow(saved), key: row.key, state: { saved: true } });
      } catch (saveError) {
        const firstFieldError = Object.values(saveError.fieldErrors || {})[0]?.[0];
        updateRow(row.key, { state: { error: firstFieldError || saveError.message } });
      }
    }

    setIsSaving(false);
  }

  // The toggle saves straight away with the row's last saved values plus the new active flag.
  async function saveActive(row, isActive) {
    updateRow(row.key, { state: null });
    try {
      const saved = await updateTier(row.id, tierPayload(row, isActive));
      updateRow(row.key, { is_active: saved.is_active, state: { saved: true } });
    } catch (toggleError) {
      updateRow(row.key, { state: { error: toggleError.message } });
    }
  }

  function handleToggle(row) {
    if (row.isNew) {
      updateRow(row.key, { is_active: !row.is_active });
      return;
    }
    if (row.is_active) setDeactivating(row);
    else saveActive(row, true);
  }

  const hasChanges = rows.some((row) => row.isDirty);

  return (
    <div className="wl-stack">
      <div className="wl-page-head">
        <h1>Pricing</h1>
        <button type="button" className="wl-btn" onClick={handleAddTier}>
          <Plus aria-hidden="true" />
          Add tier
        </button>
      </div>

      <Alert variant="info" title="Prices apply to new bookings only.">Existing bookings keep the price they paid.</Alert>

      {isLoading && <SkeletonRows count={4} label="Loading tiers…" />}
      {error && <ErrorAlert error={error} onRetry={loadTiers} />}
      {!isLoading && !error && (
        <>
          <div className="wl-table-wrap">
            <table className="wl-table wl-table--stack">
              <thead>
                <tr><th>Name</th><th>Code</th><th>Min guests</th><th>Max guests</th><th>Price (USD)</th><th>Active</th><th><span className="wl-sr-only">Result</span></th></tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <TierRow key={row.key} row={row} onChange={(changes) => updateRow(row.key, { ...changes, isDirty: true, state: null })} onToggle={() => handleToggle(row)} />
                ))}
              </tbody>
            </table>
          </div>
          <div>
            <button type="button" className={`wl-btn wl-btn--primary${isSaving ? ' is-loading' : ''}`} aria-busy={isSaving} disabled={!hasChanges || isSaving} onClick={handleSaveChanges}>
              Save changes
            </button>
          </div>
        </>
      )}

      <Dialog
        isOpen={deactivating !== null}
        title={`Deactivate ${deactivating?.name}?`}
        confirmLabel="Deactivate tier"
        onConfirm={() => { saveActive(deactivating, false); setDeactivating(null); }}
        onCancel={() => setDeactivating(null)}
      >
        Visitors won&apos;t be able to choose this tier for new bookings. Existing bookings are not changed, and you can turn it on again later.
      </Dialog>
    </div>
  );
}

function TierRow({ row, onChange, onToggle }) {
  const numberInput = (name, label, props = {}) => (
    <input className="wl-input" type="number" aria-label={`${label} for ${row.name || 'new tier'}`} value={row[name]} onChange={(event) => onChange({ [name]: event.target.value })} {...props} />
  );

  return (
    <tr className={row.isDirty ? 'is-dirty' : undefined}>
      <td data-label="Name"><input className="wl-input" aria-label="Tier name" value={row.name} maxLength={50} onChange={(event) => onChange({ name: event.target.value })} /></td>
      <td data-label="Code" className="wl-ref">
        {row.isNew
          ? <input className="wl-input wl-input--mono" aria-label="Tier code" value={row.code} maxLength={20} onChange={(event) => onChange({ code: event.target.value.toUpperCase() })} />
          : row.code}
      </td>
      <td data-label="Min guests">{numberInput('min_guests', 'Min guests', { min: 1, max: 10 })}</td>
      <td data-label="Max guests">{numberInput('max_guests', 'Max guests', { min: 1, max: 10 })}</td>
      <td data-label="Price (USD)">{numberInput('price', 'Price', { min: 0, step: '0.01' })}</td>
      <td data-label="Active">
        <button type="button" className="wl-toggle" role="switch" aria-checked={row.is_active} aria-label={`${row.name || 'New'} tier active`} onClick={onToggle} />
      </td>
      <td data-label="Result">
        {row.state?.saved && <span className="wl-row-saved">Saved</span>}
        {row.state?.error && <span className="wl-row-error" role="alert">{row.state.error}</span>}
      </td>
    </tr>
  );
}
