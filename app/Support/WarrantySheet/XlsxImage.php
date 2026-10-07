<?php

namespace App\Support\WarrantySheet;

use RuntimeException;
use ZipArchive;

/**
 * Puts a picture on the first sheet of an .xlsx file written by openspout (which can't write images): adds the
 * standard drawing parts (media, drawing + relations, content types) to the zip.
 */
class XlsxImage
{
    private const EMU_PER_PX = 9525;

    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** Anchors the image's top-left corner at the given 0-based column of row 1, scaled to $heightPx. */
    public static function insert(string $xlsx, string $image, int $column = 0, int $heightPx = 90, int $offsetPx = 0): void
    {
        $size = @getimagesize($image);
        $type = match ($size[2] ?? null) {
            IMAGETYPE_PNG => ['png', 'image/png'],
            IMAGETYPE_JPEG => ['jpeg', 'image/jpeg'],
            default => null,
        };

        if (! $size || ! $type) {
            return; // not a usable picture: the sheet just has no banner
        }

        $cy = $heightPx * self::EMU_PER_PX;
        $cx = (int) round($size[0] * $heightPx / max(1, $size[1])) * self::EMU_PER_PX;

        $zip = new ZipArchive;
        if ($zip->open($xlsx) !== true) {
            throw new RuntimeException('Could not open the Excel file to add the banner.');
        }

        try {
            $sheetPath = 'xl/worksheets/sheet1.xml';
            $sheetRels = 'xl/worksheets/_rels/sheet1.xml.rels';
            $sheet = $zip->getFromName($sheetPath);
            if ($sheet === false || str_contains($sheet, '<drawing ')) {
                return;
            }

            $zip->addFromString("xl/media/banner.{$type[0]}", (string) file_get_contents($image));
            $zip->addFromString('xl/drawings/drawing1.xml', self::drawingXml($column, $offsetPx * self::EMU_PER_PX, $cx, $cy));
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="'.self::NS_REL.'/image" Target="../media/banner.'.$type[0].'"/></Relationships>');

            // Sheet → drawing relation.
            $rels = $zip->getFromName($sheetRels) ?: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            $rels = str_replace('</Relationships>', '<Relationship Id="rIdBanner" Type="'.self::NS_REL.'/drawing" Target="../drawings/drawing1.xml"/></Relationships>', $rels);
            $zip->addFromString($sheetRels, $rels);

            // <drawing> goes before <legacyDrawing> (comments), else before </worksheet> (element order matters to Excel).
            if (! preg_match('/<worksheet[^>]*xmlns:r=/', $sheet)) {
                $sheet = preg_replace('/<worksheet\b/', '<worksheet xmlns:r="'.self::NS_REL.'"', $sheet, 1);
            }
            $tag = '<drawing r:id="rIdBanner"/>';
            $sheet = preg_match('/<(legacyDrawing|legacyDrawingHF|picture|oleObjects|controls|webPublishItems|tableParts|extLst)\b/', $sheet, $m, PREG_OFFSET_CAPTURE)
                ? substr_replace($sheet, $tag, $m[0][1], 0)
                : str_replace('</worksheet>', $tag.'</worksheet>', $sheet);
            $zip->addFromString($sheetPath, $sheet);

            $types = (string) $zip->getFromName('[Content_Types].xml');
            if (! preg_match('/Extension="'.$type[0].'"/i', $types)) {
                $types = str_replace('</Types>', '<Default Extension="'.$type[0].'" ContentType="'.$type[1].'"/></Types>', $types);
            }
            $types = str_replace('</Types>', '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>', $types);
            $zip->addFromString('[Content_Types].xml', $types);
        } finally {
            $zip->close();
        }
    }

    private static function drawingXml(int $column, int $colOffset, int $cx, int $cy): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<xdr:oneCellAnchor>'
            ."<xdr:from><xdr:col>{$column}</xdr:col><xdr:colOff>{$colOffset}</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>"
            ."<xdr:ext cx=\"{$cx}\" cy=\"{$cy}\"/>"
            .'<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Banner"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip xmlns:r="'.self::NS_REL.'" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            ."<xdr:spPr><a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"{$cx}\" cy=\"{$cy}\"/></a:xfrm><a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom></xdr:spPr>"
            .'</xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
    }
}
