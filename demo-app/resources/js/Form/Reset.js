export default function Reset(form) {
    form.reset();
    form.querySelector('textarea, input')?.focus();
}
