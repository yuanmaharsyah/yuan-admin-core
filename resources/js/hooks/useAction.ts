import { useRef, useState } from 'react';

/**
 * One server action at a time (frontend-behavior §2): `run(start)` calls
 * `start(done)` unless a run is still pending, and `pending` drives the
 * button's `loading`. The ref blocks a second click that lands before React
 * re-renders the disabled button.
 *
 *     const remove = useAction();
 *     remove.run((done) => router.delete(url, { onFinish: done }));
 *     <Button loading={remove.pending}>
 */
export function useAction() {
    const running = useRef(false);
    const [pending, setPending] = useState(false);

    return {
        pending,
        run: (start: (done: () => void) => void) => {
            if (running.current) {
                return;
            }

            running.current = true;
            setPending(true);
            start(() => {
                running.current = false;
                setPending(false);
            });
        },
    };
}
