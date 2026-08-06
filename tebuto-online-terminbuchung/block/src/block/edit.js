import WidgetConfigurator from '../shared/WidgetConfigurator'
import './editor.scss'

export default function Edit({ attributes, setAttributes }) {
	return (
		<WidgetConfigurator variant="booking" surface="inspector" attributes={attributes} setAttributes={setAttributes} />
	)
}
