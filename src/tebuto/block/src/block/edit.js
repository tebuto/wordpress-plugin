import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, ColorPicker, ToggleControl } from "@wordpress/components";
import "./editor.scss";

export default function Edit({ attributes, setAttributes }) {
	const { backgroundColor, border } = attributes;
	const [bgcolor, setBgColor] = useState(backgroundColor || "#ffffff");

	// UUID aus lokalisierten PHP-Daten holen
	const uuid = tebutoData?.uuid || "";

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
					title={__("Widget-Einstellungen", "tebuto")}
					initialOpen={true}
				>
					<label>{__("Hintergrundfarbe", "tebuto")}</label>
					<ColorPicker
						value={bgcolor}
						onChange={(color) => {
							setBgColor(color);
							setAttributes({ backgroundColor: color });
						}}
						enableAlpha
						defaultValue="#ffffff"
					/>
					<ToggleControl
						label={__("Rahmen anzeigen", "tebuto")}
						checked={border}
						onChange={(value) => setAttributes({ border: value })}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<p style={{ fontWeight: "bold", padding: 0, margin: 0 }}>
					{__("Tebuto Terminbuchung Widget", "tebuto")}
				</p>
				<small style={{ padding: 0, margin: 0 }}>
					{__("Ihre UUID:", "tebuto")} {uuid}
				</small>
			</div>
		</>
	);
}
