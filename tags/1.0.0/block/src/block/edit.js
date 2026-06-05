import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, ColorPicker, ToggleControl } from "@wordpress/components";
import "./editor.scss";

export default function Edit({ attributes, setAttributes }) {
	const { backgroundColor, border } = attributes;
	const [bgcolor, setBgColor] = useState(backgroundColor || "#ffffff");

	const blockProps = useBlockProps({
		style: {
			backgroundColor: backgroundColor || "ffffff",
			border: border ? "1px solid #000" : "none",
		},
	});

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__("Widget-Einstellungen", "tebuto-online-terminbuchung")}
					initialOpen={true}
				>
					<label>{__("Hintergrundfarbe", "tebuto-online-terminbuchung")}</label>
					<ColorPicker
						value={bgcolor}
						onChange={(color) => {
							setBgColor(color);
							setAttributes({ backgroundColor: color });
						}}
						enableAlpha
						defaultValue={bgcolor}
					/>
					<ToggleControl
						label={__("Rahmen anzeigen", "tebuto-online-terminbuchung")}
						checked={border}
						onChange={(value) => setAttributes({ border: value })}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<p style={{ fontWeight: "bold", padding: 0, margin: 0 }}>
					{__("Tebuto Terminbuchung Widget", "tebuto-online-terminbuchung")}
				</p>
				<small style={{ padding: 0, margin: 0 }}>
					Hier werden Ihre öffentlichen Termine angezeigt
				</small>
			</div>
		</>
	);
}
