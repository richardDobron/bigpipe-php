import { requireModule } from 'bigpipe-util/dist/ModuleRegistry';

export default class ConfigReader {
    // Read when it is used, not when the file is loaded, so that a later define() is seen.
    show(output) {
        const { locale, greeting, request } = requireModule('AppConfig');

        output.textContent = `${greeting} (locale: ${locale}, timeout: ${request.timeout} ms, retries: ${request.retries})`;
    }
}
