const { toast, snackbar } = require('tailwind-toast')

export default class Toastr {
    error(message) {
        toast()
            .default('Oops!', message)
            .with({
                shape: 'square',
                duration: 4000,
                speed: 1000,
                positionX: 'center',
                positionY: 'bottom',
                color: 'bg-red-800',
                fontColor: 'gray',
                fontTone: 200
            })
            .show();
    }

    success(message) {
        toast()
            .default('Success!', message)
            .with({
                shape: 'square',
                duration: 4000,
                speed: 1000,
                positionX: 'center',
                positionY: 'bottom',
                color: 'bg-green-800',
                fontColor: 'gray',
                fontTone: 200
            })
            .show();
    }
}
