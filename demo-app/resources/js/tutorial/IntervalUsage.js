import AsyncRequest from "bigpipe-util/dist/async/AsyncRequest";
import { setInterval } from "bigpipe-util/dist/core/TimerStorage";

export default class IntervalUsage {
    init(endpoint) {
        const timeout = 2500;

        // The interval of TimerStorage is cleared by a page transition, so it ends with the page.
        setInterval(() => {
            new AsyncRequest(endpoint).send();
        }, timeout);
    }
}
