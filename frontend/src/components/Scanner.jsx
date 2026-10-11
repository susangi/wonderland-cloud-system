import { useEffect, useRef, useState } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { CameraOff } from 'lucide-react';

// Camera QR scanner (design/components/Scanner) using html5-qrcode.
// After a scan we pause and let the page call the API. "isPaused" from the page resumes it.
// If the camera is blocked or the page is not HTTPS, staff use the manual reference box instead.

const CAMERA_ELEMENT_ID = 'wl-qr-camera';

export default function Scanner({ onScan, isPaused, isEnabled = true }) {
  const [cameraState, setCameraState] = useState('starting');
  const scannerRef = useRef(null);
  // Keep the latest onScan without restarting the camera on every render.
  const onScanRef = useRef(onScan);
  onScanRef.current = onScan;

  useEffect(() => {
    if (!isEnabled) return undefined;

    const scanner = new Html5Qrcode(CAMERA_ELEMENT_ID, { verbose: false });
    scannerRef.current = scanner;
    setCameraState('starting');

    scanner
      .start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 }, (decodedText) => onScanRef.current(decodedText.trim()))
      .then(() => setCameraState('scanning'))
      .catch(() => setCameraState('off'));

    return () => {
      if (scanner.isScanning) {
        scanner.stop().catch(() => {});
      }
    };
  }, [isEnabled]);

  // Pause while the page shows a result, resume afterwards.
  useEffect(() => {
    const scanner = scannerRef.current;
    if (!scanner || cameraState !== 'scanning') return;

    try {
      if (isPaused) scanner.pause(true);
      else scanner.resume();
    } catch {
      // pause/resume throw if the state is already the same. Nothing to do.
    }
  }, [isPaused, cameraState]);

  return (
    <div className="wl-scanner" aria-label="Camera scanner">
      <div id={CAMERA_ELEMENT_ID} className="wl-scanner__video" />
      {cameraState === 'starting' && isEnabled && (
        <div className="wl-scanner__off">
          <div className="wl-spinner" aria-hidden="true" />
          <p>Starting camera…</p>
        </div>
      )}
      {cameraState === 'scanning' && (
        <>
          <div className="wl-scanner__frame"><i /></div>
          {!isPaused && <div className="wl-scanner__line" />}
          <p className="wl-scanner__hint">{isPaused ? 'Checking ticket…' : 'Point at the ticket QR code'}</p>
        </>
      )}
      {(cameraState === 'off' || !isEnabled) && (
        <div className="wl-scanner__off">
          <CameraOff aria-hidden="true" />
          <strong>{isEnabled ? 'Camera not available' : 'Scanner is off'}</strong>
          <p>Use the booking reference below.</p>
        </div>
      )}
    </div>
  );
}
