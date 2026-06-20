$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0

$pdfPath = "C:\Users\HP\Desktop\cpf.pdf"
$docxPath = "C:\Users\HP\Desktop\cpf.docx"

Write-Host "Opening PDF: $pdfPath"
try {
    # Open the PDF file. Word automatically converts PDF to Word document when opened this way.
    # Open parameters: (FileName, ConfirmConversions, ReadOnly, ...)
    $doc = $word.Documents.Open($pdfPath, $false, $false)
    
    Write-Host "Saving as DOCX: $docxPath"
    # wdFormatXMLDocument = 12 (standard .docx file format)
    $doc.SaveAs2($docxPath, 12)
    $doc.Close()
    Write-Host "Conversion completed successfully!"
} catch {
    Write-Error "Failed to convert: $_"
} finally {
    $word.Quit()
    # Release COM object
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    Remove-Variable word -ErrorAction SilentlyContinue
}
