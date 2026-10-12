// Empty state (design/components/States): round icon, title, one line of help and one action.
export default function EmptyState({ icon: Icon, title, text, action }) {
  return (
    <div className="wl-empty">
      {Icon && (
        <div className="wl-empty__art">
          <Icon aria-hidden="true" />
        </div>
      )}
      <h2 className="wl-empty__title">{title}</h2>
      {text && <p>{text}</p>}
      {action}
    </div>
  );
}
