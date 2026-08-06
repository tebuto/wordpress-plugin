import WidgetConfigurator from '../shared/WidgetConfigurator'
import '../block/editor.scss'

export default function Edit({ attributes, setAttributes }) {
	return (
		<WidgetConfigurator variant="seminars" surface="inspector" attributes={attributes} setAttributes={setAttributes} />
	)
}
