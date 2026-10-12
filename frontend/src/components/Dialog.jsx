import { useEffect, useRef } from 'react';

// Confirmation before an admin action that changes what visitors see (design/components/Dialog).
// The native <dialog> with showModal() traps focus and closes on Esc for us.
export default function Dialog({ isOpen, title, children, confirmLabel, onConfirm, onCancel, isBusy = false }) {
  const dialogRef = useRef(null);
  const cancelRef = useRef(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;

    if (isOpen && !dialog.open) {
      dialog.showModal();
      cancelRef.current?.focus();
    } else if (!isOpen && dialog.open) {
      dialog.close();
    }
  }, [isOpen]);

  return (
    <dialog ref={dialogRef} className="wl-dialog" aria-labelledby="wl-dialog-title" onCancel={onCancel} style={{ border: 0 }}>
      <h2 className="wl-dialog__title" id="wl-dialog-title">{title}</h2>
      <p className="wl-muted">{children}</p>
      <div className="wl-dialog__actions">
        <button ref={cancelRef} type="button" className="wl-btn" onClick={onCancel}>Cancel</button>
        <button type="button" className={`wl-btn wl-btn--danger${isBusy ? ' is-loading' : ''}`} aria-busy={isBusy} onClick={onConfirm}>
          {confirmLabel}
        </button>
      </div>
    </dialog>
  );
}
