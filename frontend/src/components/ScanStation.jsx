import { useRef, useState } from 'react';
import Scanner from './Scanner';
import ScanResult, { scanErrorText } from './ScanResult';
import { formatTime } from '../utils/format';

// Shared by Gate check-in and Ride scan (design/Screens.md 11 and 12):
// result panel, camera scanner, manual reference box and the last 5 scans.
// recordScan(reference) calls the API and returns { title, detail, extra } for the green panel.

const RESUME_AFTER_MS = 2500;

export default function ScanStation({ recordScan, submitLabel, isEnabled = true, disabledHint, extraResult }) {
  const [result, setResult] = useState(null);
  const [isBusy, setIsBusy] = useState(false);
  const [manualReference, setManualReference] = useState('');
  const [recentScans, setRecentScans] = useState([]);
  const resumeTimer = useRef(null);

  async function handleReference(reference) {
    if (isBusy || !reference) return;

    setIsBusy(true);
    clearTimeout(resumeTimer.current);
    let newResult;

    try {
      const success = await recordScan(reference.toUpperCase());
      newResult = { kind: 'success', reference: reference.toUpperCase(), scannedAt: new Date().toISOString(), ...success };
      setManualReference('');
      navigator.vibrate?.(80);
    } catch (error) {
      newResult = { kind: 'error', reference: reference.toUpperCase(), scannedAt: new Date().toISOString(), error };
      navigator.vibrate?.([80, 60, 80]);
    }

    setResult(newResult);
    setRecentScans([newResult, ...recentScans].slice(0, 5));
    // The camera starts again after 2.5 seconds, or at once with "Scan next".
    resumeTimer.current = setTimeout(() => setIsBusy(false), RESUME_AFTER_MS);
  }

  function handleScanNext() {
    clearTimeout(resumeTimer.current);
    setIsBusy(false);
  }

  function handleManualSubmit(event) {
    event.preventDefault();
    handleReference(manualReference.trim());
  }

  return (
    <div className="wl-scan-layout">
      <div className="wl-stack">
        {!isEnabled && disabledHint && <p className="wl-muted">{disabledHint}</p>}
        <Scanner onScan={handleReference} isPaused={isBusy} isEnabled={isEnabled} />
        <form className="wl-stack" onSubmit={handleManualSubmit}>
          <div className="wl-field">
            <label className="wl-label" htmlFor="manual-reference">Booking reference</label>
            <input
              id="manual-reference"
              className="wl-input wl-input--mono wl-input--lg"
              placeholder="WL-YYYYMMDD-XXXXXX"
              autoCapitalize="characters"
              autoComplete="off"
              spellCheck="false"
              value={manualReference}
              disabled={!isEnabled}
              onChange={(event) => setManualReference(event.target.value)}
            />
            <p className="wl-hint">Type it if the QR code won't scan.</p>
          </div>
          <button type="submit" className="wl-btn wl-btn--primary wl-btn--lg wl-btn--block" disabled={!isEnabled || !manualReference.trim() || isBusy}>
            {submitLabel}
          </button>
        </form>
      </div>

      <div className="wl-stack wl-scan-first">
        <ScanResult result={result} />
        {result?.kind === 'success' && extraResult?.(result)}
        {result && isBusy && <button type="button" className="wl-btn wl-btn--lg wl-btn--block" onClick={handleScanNext}>Scan next</button>}
        {recentScans.length > 0 && (
          <div className="wl-card">
            <h2 className="wl-chart-card__title">Recent scans</h2>
            <ul className="wl-recent">
              {recentScans.map((scan, index) => (
                <li key={`${scan.reference}-${index}`}>
                  <span className="wl-ref">{scan.reference}</span>
                  <span>{scan.kind === 'success' ? scan.title : scanErrorText(scan.error).title}</span>
                  <span className="wl-timeline__time">{formatTime(scan.scannedAt)}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </div>
  );
}
