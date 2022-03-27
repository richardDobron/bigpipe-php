import AsyncRequest from "bigpipe-util/src/async/AsyncRequest";

export default class IntervalUsage {
    init(endpoint) {
        const timeout = 2500;

        setInterval(() => {
            (new AsyncRequest(endpoint))
                .setHandler((response) => {
                    // ...
                })
                .send();
        }, timeout);
    }
}
