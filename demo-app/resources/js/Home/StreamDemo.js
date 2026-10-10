import { requestAnimationFrame, cancelAnimationFrame } from 'bigpipe-util/dist/core/TimerStorage';

// The demo of the landing page: the parts of a page arrive with a waterfall, and a clock runs with it
// (2 ms of animation for 1 ms on the server). The timers of TimerStorage end with a page transition.
export default class StreamDemo {
    init(demo) {
        this.demo = demo;
        this.clock = demo.querySelector('.clock');

        demo.querySelector('.replay').addEventListener('click', () => this.play());
        this.play();
    }

    play() {
        cancelAnimationFrame(this.frame);
        this.demo.classList.remove('play');
        void this.demo.offsetWidth;
        this.demo.classList.add('play');

        const start = performance.now();
        const tick = now => {
            const ms = Math.min(1200, Math.round((now - start) / 2));
            this.clock.textContent = `${ms} ms`;

            if (ms < 1200) {
                this.frame = requestAnimationFrame(tick);
            }
        };

        tick(start);
    }
}
