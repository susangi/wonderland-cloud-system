import { Check, CircleAlert, CreditCard, Info, TriangleAlert } from 'lucide-react';

// Inline message (design/components/Alert). Title says what happened, body says what to do next.
const ICONS = { info: Info, success: Check, warning: TriangleAlert, danger: CircleAlert, demo: CreditCard };

export default function Alert({ variant = 'info', title, children, actions }) {
  const Icon = ICONS[variant];
  const variantClass = variant === 'info' ? '' : ` wl-alert--${variant}`;
  // Errors and warnings are announced at once, success and info politely.
  const role = variant === 'danger' || variant === 'warning' ? 'alert' : 'status';

  return (
    <div className={`wl-alert${variantClass}`} role={role}>
      <Icon aria-hidden="true" />
      <div>
        {title && <strong className="wl-alert__title">{title}</strong>}
        {children && <div className="wl-alert__body">{children}</div>}
        {actions && <div className="wl-alert__actions">{actions}</div>}
      </div>
    </div>
  );
}

// The usual "Something went wrong" alert with a Try again button.
export function ErrorAlert({ error, onRetry, title = 'Something went wrong' }) {
  return (
    <Alert
      variant="danger"
      title={title}
      actions={onRetry && <button type="button" className="wl-btn wl-btn--sm" onClick={onRetry}>Try again</button>}
    >
      {error?.message || 'Check your connection and try again.'}
    </Alert>
  );
}
