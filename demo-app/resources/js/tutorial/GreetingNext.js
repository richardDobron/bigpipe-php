// The new variant of tutorial/Greeting, for the users the server turned it on for.
export default class GreetingNext {
    static variant = 'tutorial/GreetingNext';

    greet(element, name) {
        const hour = new Date().getHours();
        const part = hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';

        element.dataset.variant = 'next';
        element.textContent = `Good ${part}, ${name}! This is the new greeting.`;
    }
}
