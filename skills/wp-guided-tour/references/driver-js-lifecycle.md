# Driver.js Lifecycle Reference

## Critical: completion vs dismiss

`onDestroyed` fires for BOTH completion and early dismiss. Never use it for completion tracking.

```
✅ Correct: inject onNextClick on the last step only
❌ Wrong:   use onDestroyed to call markTourCompleted
```

## startTour() with completion tracking

```js
let currentTour = null;

function startTour(scope) {
    if (!window.driver?.js?.driver) return false;

    const config = window.MYPLUGIN?.tours?.[scope];
    if (!config) return false;

    if (currentTour) currentTour.destroy();

    const steps     = config.steps;
    const lastIndex = steps.length - 1;

    const stepsWithCompletion = steps.map((step, i) => {
        if (i !== lastIndex) return step;
        return {
            ...step,
            popover: {
                ...step.popover,
                onNextClick: () => {
                    markTourCompleted(scope);   // fires ONLY on Done click
                    currentTour.destroy();
                },
            },
        };
    });

    currentTour = window.driver.js.driver({
        showProgress: true,
        smoothScroll: true,
        showButtons:  ['next', 'previous', 'close'],
        steps:        stepsWithCompletion,
        onDestroyed:  () => { currentTour = null; },
    });

    setTimeout(() => currentTour.drive(), 100);
    return true;
}
```

## Completion storage

```js
function markTourCompleted(scope) {
    localStorage.setItem(`myplugin_${scope}_tour_completed`, 'true');
}

function isTourCompleted(scope) {
    return localStorage.getItem(`myplugin_${scope}_tour_completed`) === 'true';
}
```

## autoStartTours() pattern

```js
function autoStartTours() {
    const scope = getCurrentScope();
    if (!scope || isTourCompleted(scope)) return;

    const config = window.MYPLUGIN?.tours?.[scope];
    if (config?.autoStart) startTour(scope);
}

document.addEventListener('DOMContentLoaded', autoStartTours);
window.addEventListener('hashchange', () => setTimeout(autoStartTours, 500));
```

## IIFE namespace

Driver.js v1 IIFE build exposes a double namespace — always access as:
```js
window.driver.js.driver({ ... })   // correct
window.driver({ ... })             // wrong — undefined
```
