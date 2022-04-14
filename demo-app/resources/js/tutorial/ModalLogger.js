export default class ModalLogger
{
    constructor(dialog) {
        console.log('dialog data:', dialog);

        dialog.on('show', (event) => {
            console.log('event: show', event)
        });

        dialog.on('shown', (event) => {
            console.log('event: shown', event)
        });

        dialog.on('hide', (event) => {
            console.log('event: hide', event)
        });

        dialog.on('hidden', (event) => {
            console.log('event: hidden', event)
        });
    }
}
