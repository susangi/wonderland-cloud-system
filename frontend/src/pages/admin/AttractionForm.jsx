import { useState } from 'react';
import { ErrorAlert } from '../../components/Alert';
import { CATEGORIES, THRILL_LEVELS } from '../../utils/status';

// Create / edit attraction form in a right side panel (design/Screens.md section 9).
// Server validation errors (422) are shown under the matching field.

const EMPTY_ATTRACTION = { name: '', description: '', category: 'rollercoaster', thrill_level: 'medium', min_age: 0, min_height_cm: 0 };

export default function AttractionForm({ attraction, onSave, onCancel }) {
  const [values, setValues] = useState(attraction ? { ...EMPTY_ATTRACTION, ...attraction } : EMPTY_ATTRACTION);
  const [fieldErrors, setFieldErrors] = useState({});
  const [error, setError] = useState(null);
  const [isSaving, setIsSaving] = useState(false);

  function updateValue(name, value) {
    setValues({ ...values, [name]: value });
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setIsSaving(true);
    setError(null);
    setFieldErrors({});

    try {
      await onSave({
        name: values.name,
        description: values.description,
        category: values.category,
        thrill_level: values.thrill_level,
        min_age: Number(values.min_age),
        min_height_cm: Number(values.min_height_cm),
      });
    } catch (saveError) {
      if (saveError.code === 'VALIDATION_ERROR') setFieldErrors(saveError.fieldErrors);
      else setError(saveError);
      setIsSaving(false);
    }
  }

  const field = (name, label, input) => (
    <div className={`wl-field${fieldErrors[name] ? ' is-invalid' : ''}`}>
      <label className="wl-label" htmlFor={`attraction-${name}`}>{label}</label>
      {input}
      {fieldErrors[name] && <p className="wl-error-text" id={`attraction-${name}-error`}>{fieldErrors[name][0]}</p>}
    </div>
  );

  const inputProps = (name) => ({
    id: `attraction-${name}`,
    value: values[name],
    onChange: (event) => updateValue(name, event.target.value),
    'aria-invalid': fieldErrors[name] ? 'true' : undefined,
    'aria-describedby': fieldErrors[name] ? `attraction-${name}-error` : undefined,
  });

  return (
    <>
      <div className="wl-panel-backdrop" onClick={onCancel} aria-hidden="true" />
      <aside className="wl-panel" role="dialog" aria-modal="true" aria-labelledby="attraction-form-title">
        <form className="wl-stack" onSubmit={handleSubmit}>
          <h2 id="attraction-form-title">{attraction ? `Edit ${attraction.name}` : 'Add attraction'}</h2>
          {error && <ErrorAlert error={error} />}
          {field('name', 'Name', <input className="wl-input" maxLength={100} required {...inputProps('name')} />)}
          {field('description', 'Description', <textarea className="wl-textarea" required {...inputProps('description')} />)}
          {field('category', 'Category', (
            <select className="wl-select" {...inputProps('category')}>
              {CATEGORIES.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
          ))}
          {field('thrill_level', 'Thrill level', (
            <select className="wl-select" {...inputProps('thrill_level')}>
              {THRILL_LEVELS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
          ))}
          <div className="wl-form-row">
            {field('min_age', 'Min age', <input className="wl-input" type="number" min={0} max={100} {...inputProps('min_age')} />)}
            {field('min_height_cm', 'Min height cm', <input className="wl-input" type="number" min={0} max={250} {...inputProps('min_height_cm')} />)}
          </div>
          <div className="wl-row">
            <button type="submit" className={`wl-btn wl-btn--primary${isSaving ? ' is-loading' : ''}`} aria-busy={isSaving}>Save attraction</button>
            <button type="button" className="wl-btn" onClick={onCancel}>Cancel</button>
          </div>
        </form>
      </aside>
    </>
  );
}
