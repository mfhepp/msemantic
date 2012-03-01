<?php

class Semantium_MSemanticBasic_Model_Sitemap
{
	
	public function createSitemap()
	{



### was wird hier importiert

# require ("../config/config.php");

$inhalt = '';
$inhalt_produkte = '';
$inhalt_hersteller = '';
$inhalt_index = '';
$blog_text = '';
$a = '0';
$g = '0';
$h = '0';
$l = '0';
$x = '0';


// kategorien

// SQL-Abfrage (kategorien: url und datum)
$query = mysql_query("SELECT catalog_category_entity.updated_at AS 'datum', core_url_rewrite.request_path AS 'url'
FROM catalog_category_entity
INNER JOIN core_url_rewrite
ON catalog_category_entity.entity_id = core_url_rewrite.category_id
WHERE core_url_rewrite.product_id IS NULL
AND core_url_rewrite.request_path NOT LIKE '%hersteller%'
AND core_url_rewrite.request_path NOT LIKE '%preisaktion%'
");

echo mysql_error();

while($reihe = mysql_fetch_array($query)) {
	$inhalt .= '<url><loc>http://www.arzneimittel.de/'.$reihe['url'].'</loc><lastmod>'.str_replace(' ','T',$reihe['datum']).'-02:00</lastmod><changefreq>daily</changefreq><priority>0.5</priority></url>'."\n";
	$g++;
	//echo $g.'<br/>';
}

$startseite = '<url><loc>http://www.arzneimittel.de</loc><lastmod>'.date('c').'</lastmod><changefreq>always</changefreq><priority>1</priority></url>'."\n";	// date('Y-m-d')
/*
	$dateiname = '../../sitemap_kategorien.xml';
	$datei = fopen ($dateiname,"w");
	fwrite($datei,'<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$startseite.$inhalt.'</urlset>');
	fclose($datei);
*/
	$dateiname = '../../sitemap.xml';
	$datei = fopen ($dateiname,"w");
	fwrite($datei,'<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$startseite.$inhalt.'</urlset>');
	fclose($datei);

echo 'urls (kategorien): '.$g.'<br/>';


// produkte - mehrere dateien


// datum für ratgeber
$handle1 = fopen ("sitemap_datei_blog.csv","r");       // Datei zum Lesen öffnen

while ( ($data= fgetcsv ($handle1, 1000, ";")) !== FALSE ) {
	$blog[str_pad($data[0], 7, "0", STR_PAD_LEFT)] = $data[1];
	$blog_text .= str_pad($data[0], 7, "0", STR_PAD_LEFT).';';
}

fclose($handle1);

//print_r($blog);

// SQL-Abfrage (produkte: url und datum)
$query_produkte = mysql_query("SELECT catalog_product_entity.updated_at AS 'datum', core_url_rewrite.request_path AS 'url', catalog_product_entity.sku AS 'pzn'
FROM catalog_product_entity
INNER JOIN core_url_rewrite
ON catalog_product_entity.entity_id = core_url_rewrite.product_id
WHERE core_url_rewrite.category_id IS NULL
");

echo mysql_error();

while($reihe_produkte = mysql_fetch_array($query_produkte)) {
	$l++;
	$modolus = $l % 50000;
	if ($modolus == '0') $a++;

	// wenn pzn in blog-datei, dann nehme aktuelleres datum
	
	// abfrage ob pzn in array, wenn ja abfrage ob datum aktueller
	if (strstr($blog_text,str_pad($reihe_produkte['pzn'], 7, "0", STR_PAD_LEFT))) {
		if ($reihe_produkte['datum'] < $blog[str_pad($reihe_produkte['pzn'], 7, "0", STR_PAD_LEFT)]) {
			$datum = str_replace(' ','T',$blog[str_pad($reihe_produkte['pzn'], 7, "0", STR_PAD_LEFT)]).'+00:00';
			//echo str_pad($reihe_produkte['pzn'], 7, "0", STR_PAD_LEFT).'neues datum<br/>';
		}
		else {
			$datum = str_replace(' ','T',$reihe_produkte['datum']).'-02:00';
		}
	}
	else { 
		$datum = str_replace(' ','T',$reihe_produkte['datum']).'-02:00';
	}

	$inhalt_produkte[$a] .= '<url><loc>http://www.arzneimittel.de/'.$reihe_produkte['url'].'</loc><lastmod>'.$datum.'</lastmod><changefreq>daily</changefreq><priority>1</priority></url>'."\n";
	$h++;
}

for ($i='0'; $i < '5'; $i++) {
	$dateiname = '../../sitemap_produkte_'.$i.'.xml';
	$datei = fopen ($dateiname,"w");
	fwrite($datei,'<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$inhalt_produkte[$i].'</urlset>');
	fclose($datei);
}

echo 'urls (produkte): '.$h.'<br/>';



// sitemap-index
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap_produkte_1.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap_produkte_2.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap_produkte_3.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap_produkte_4.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/sitemap_hersteller.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";
$inhalt_index .= '<sitemap><loc>http://www.arzneimittel.de/blog/sitemap.xml</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>'."\n";



	$dateiname = '../../sitemap_index.xml';
	$datei = fopen ($dateiname,"w");
	fwrite($datei,'<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$inhalt_index.'</sitemapindex>');
	fclose($datei);

echo 'sitemap-index erstellt<br/>';
}


}

?>