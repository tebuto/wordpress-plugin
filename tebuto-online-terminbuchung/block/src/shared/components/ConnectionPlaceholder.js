import { Button, Placeholder } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { getConnectUrl } from '../theme'

export default function ConnectionPlaceholder({ expired }) {
	if (expired) {
		return (
			<Placeholder
				label={__('Sitzung abgelaufen', 'tebuto-online-terminbuchung')}
				instructions={__(
					'Deine Verbindung zu Tebuto ist abgelaufen. Bitte melde dich erneut an, um das Widget zu konfigurieren.',
					'tebuto-online-terminbuchung'
				)}
			>
				<Button variant="primary" href={getConnectUrl()}>
					{__('Erneut bei Tebuto anmelden', 'tebuto-online-terminbuchung')}
				</Button>
			</Placeholder>
		)
	}

	return (
		<Placeholder
			label={__('Tebuto nicht verbunden', 'tebuto-online-terminbuchung')}
			instructions={__(
				'Bitte verbinde zuerst dein Tebuto-Konto in den Plugin-Einstellungen.',
				'tebuto-online-terminbuchung'
			)}
		>
			<Button variant="primary" href={getConnectUrl()}>
				{__('Jetzt verbinden', 'tebuto-online-terminbuchung')}
			</Button>
		</Placeholder>
	)
}
