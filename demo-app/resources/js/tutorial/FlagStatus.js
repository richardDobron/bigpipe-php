import { ifRequired } from 'bigpipe-util/dist/ModuleRegistry';

// Shows which module tutorial/Greeting stands for. Called before the greeting: when the server turned the module off,
// its call does nothing, so the greeting stays empty, and ifRequired() runs the fallback.
export default class FlagStatus {
    show(greeting, status) {
        delete greeting.dataset.variant;
        greeting.textContent = 'Nothing ran.';

        status.textContent = ifRequired(
            'tutorial/Greeting',
            Greeting => `tutorial/Greeting is ${Greeting.variant}: the call runs it.`,
            () => 'tutorial/Greeting is off: its call did nothing, ifRequired() ran the fallback.',
        );
    }
}
