<?php
error_reporting(E_STRICT);

$result = array();

$request_type = $_POST['request_type'];
$docbook_folder = $_POST['docbook_name'];

if( $request_type == "getDocbook" ) {
	mkdir( "./uploads/$docbook_folder" );
	mkdir( "./uploads/$docbook_folder/images" );

	$total = count( $_FILES['files']['name'] );
	for( $i = 0; $i < $total; $i++ ) {
		$tmpFilePath = $_FILES['files']['tmp_name'][$i];
		if ( !empty( $tmpFilePath ) ) {
			$filename = $_FILES['files']['name'][$i];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if ( $ext == "html" || $ext == "xsl" || $ext == "json" || $ext == "css" || $ext == "pdf" || $ext == "txt" || $ext == "zip" ) {
				move_uploaded_file( $tmpFilePath, "./uploads/$docbook_folder/$filename" );
				if ( $ext == "xsl" ) {
					$xsl_contents = file_get_contents( "./uploads/$docbook_folder/$filename" );
					$docbookXslPath = realpath( __DIR__ .'/docbook-xsl-1.79.1/fo/docbook.xsl' );
					$xsl_contents = str_replace( 'DOCBOOKXSLPLACEHOLDER', $docbookXslPath, $xsl_contents );
					file_put_contents( "./uploads/$docbook_folder/$filename", $xsl_contents );
				} else if ( $ext == "zip" ) {
					if ( class_exists( "ZipArchive" ) ) {
						$zip = new ZipArchive;
						$res = $zip->open( "./uploads/$docbook_folder/$filename" );
						if ($res === TRUE) {
							$zip->extractTo( "./uploads/$docbook_folder/" . str_replace( ".zip", "", $filename ) );
							$zip->close();
						}
					} else {
						shell_exec( "unzip $filename -d ./uploads/$docbook_folder/" . str_replace( ".zip", "", $filename ) );
					}
				} else if ( $filename == "xsl_repository.json" ) {
					$repo_path = json_decode( file_get_contents( "./uploads/$docbook_folder/$filename" ) )['DocBookExportXSLRepository'];
					shell_exec( "git clone $repo_path ./uploads/$docbook_folder/" . basename( $repo_path ) );
					shell_exec( "git -C ./uploads/$docbook_folder/" . basename( $repo_path ) . " pull -s recursive -X theirs" );
				}
			} else {
				move_uploaded_file( $tmpFilePath, "./uploads/$docbook_folder/images/$filename" );
			}
		}
	}

	$result['result'] = "success";
	$result['status'] = "In Queue";
	file_put_contents( "./uploads/$docbook_folder/$docbook_folder.json", json_encode( $result ) );
	exec( "php generateDocbook.php $docbook_folder" );
} else if ( $request_type == "getDocbookStatus" ) {
	$result = json_decode( file_get_contents( "./uploads/$docbook_folder/$docbook_folder.json" ), true );
} else {
	$result['result'] = "failed";
	$result['error'] = "Unrecognized command";
}

echo json_encode( $result );