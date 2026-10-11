// A variant of tutorial/Greeting: the server chooses it with defineCond('tutorial/Greeting', 'tutorial/GreetingClassic').
export default class GreetingClassic {
    static variant = 'tutorial/GreetingClassic';

    greet(element, name) {
        element.dataset.variant = 'classic';
        element.textContent = `Hello, ${name}.`;
    }
}
