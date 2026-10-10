import Arbiter from 'bigpipe-util/dist/core/Arbiter';

// A widget that knows nothing about the others: it listens for ORDER/PLACED and counts the orders.
export default class OrderBadge {
    init(element) {
        const subscription = new Arbiter().subscribe('ORDER/PLACED', order => {
            // The element is gone, e.g. after a page transition: stop listening.
            if (!element.isConnected) {
                subscription.remove();
                return;
            }

            element.querySelector('.count').textContent = String(order.count);
            element.querySelector('.unit').textContent = order.count === 1 ? 'order' : 'orders';
            element.querySelector('.total').textContent = `$${order.total.toFixed(2)}`;
            element.classList.remove('bump');
            void element.offsetWidth;
            element.classList.add('bump');
        });
    }
}
