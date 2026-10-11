// Progress through the 4 booking steps (design/components/Stepper). It is not a menu:
// you cannot jump forward past an unfinished step.
export const BOOKING_STEPS = ['Ticket', 'Date & time', 'Review', 'Payment'];

export default function Stepper({ currentStep }) {
  return (
    <nav aria-label="Booking progress">
      <ol className="wl-stepper">
        {BOOKING_STEPS.map((label, index) => {
          const stepNumber = index + 1;
          const isDone = stepNumber < currentStep;
          const isCurrent = stepNumber === currentStep;

          return (
            <li key={label} className={isDone ? 'is-done' : undefined} aria-current={isCurrent ? 'step' : undefined}>
              <span className="wl-stepper__label">{label}</span>
            </li>
          );
        })}
      </ol>
      <p className="wl-stepper__mobile">Step {currentStep} of {BOOKING_STEPS.length}</p>
    </nav>
  );
}
