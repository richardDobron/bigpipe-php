import Arbiter from 'bigpipe-util/dist/core/Arbiter';

// Another widget on the same event. Subscribed after the event was informed with informState(), it is called
// right away with the last order.
export default class OrderLog {
    init(list) {
        const subscription = new Arbiter().subscribe('ORDER/PLACED', order => {
            if (!list.isConnected) {
                subscription.remove();
                return;
            }

            const item = document.createElement('li');
            item.textContent = `#${order.id} · ${order.product} · $${order.price.toFixed(2)}`;
            list.querySelector('.empty')?.remove();
            list.prepend(item);
        });
    }
}
