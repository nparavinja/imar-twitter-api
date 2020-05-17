<html>
</html>
<?php
session_start();

// require config and twitter helper
// config sadrzi kljuceve za api i callback url
require 'config.php';
require 'twitter-login-php/autoload.php';
require 'Baza.php';
use Abraham\TwitterOAuth\TwitterOAuth;


// poslednji korak autentikacije - imamo generisan twitter access token
if ( isset( $_SESSION['twitter_access_token'] ) && $_SESSION['twitter_access_token'] ) {
    $isLoggedIn = true;	
} elseif ( isset( $_GET['oauth_verifier'] ) && isset( $_GET['oauth_token'] ) && isset( $_SESSION['oauth_token'] ) && $_GET['oauth_token'] == $_SESSION['oauth_token'] ) { // coming from twitter callback url
    
    // saljemo konekciju sa request tokenom
    $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );
    
    // dobijamo access token i cuvamo ga
    $access_token = $connection->oauth( "oauth/access_token", array( "oauth_verifier" => $_GET['oauth_verifier'] ) );
    $_SESSION['twitter_access_token'] = $access_token;

    // korisnik je sada prijavljen
    $isLoggedIn = true;
} else {

     // korisnik nije autentifikovan
    // kreiranje TwitterOAuth objekta - komunikacija sa apijem 
    $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET );

    //dobijamo request token od twittera
    $request_token = $connection->oauth( 'oauth/request_token', array( 'oauth_callback' => OAUTH_CALLBACK ) );

    // cuvamo twitter token info u sesiju
    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    // korisnik nije jos prijavljen
    $isLoggedIn = false;
}


if ( $isLoggedIn ) {
     // korisnik je sada prijavljen
    // uzimamo token info iz sesije, za slanje zahteva za tvitove i kreiranje novog tvita
    $oauthToken = $_SESSION['twitter_access_token']['oauth_token'];
    $oauthTokenSecret = $_SESSION['twitter_access_token']['oauth_token_secret'];

    $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $oauthToken, $oauthTokenSecret );

    // get zahtev za informacijama o korisniku
    $user = $connection->get( "account/verify_credentials", ['include_email' => 'true'] );

     // provera za greske
    if ( property_exists( $user, 'errors' ) ) {
        $_SESSION = array();
        header( 'Refresh:0' );
    } else {
         // prikaz podataka
        ?>
        <img src="<?php echo $user->profile_image_url; ?>" />
        <br />
        <b>Korisnik:</b> <?php echo $user->name; ?>
        <br />
        <b>Lokacija:</b> <?php echo $user->location; ?>
        <br />
        <b>Korisnicko ime:</b> <?php echo $user->screen_name; ?>
        <br />
        <b>Datum kreiranja naloga:</b> <?php echo $user->created_at; ?>
        <br />
        <b>Broj pratilaca:</b> <?php echo $user->followers_count; ?>
        <br />
        <b>Prati:</b> <?php echo $user->friends_count; ?>
        <br />
<?php

    // sada generisemo nase korisnicko ime i pass za tviter username korisnika
    // enter into database
    $baza = new Baza("imar");
    $usernameTwitter = $user->screen_name;
    $korisnik = $baza->vratiKorisnika($usernameTwitter);
    if(isset($korisnik->UsernameTwitter)) {
        //korisnik sa ovim username vec postoji u bazi
        echo "<b>Username generisan za app: </b>".$korisnik->Username;
        echo "<br>"; 
        echo "<b>Password generisan za app: </b> ".$korisnik->Password;
        echo "<br>";

    } else {
        // dodaj korisnika u bazu
        // generisi username i pass
        $id = $baza->vratiSledeciKorisnikID();
        $username = "korisnik".$id;
        $password = "password".$id;
        $rows = "KorisnikID, Ime, Username, Password, UsernameTwitter, BrojPratilaca, Prati, Slika";
        $values = [$id, $user->name, $username, $password, $user->screen_name, $user->followers_count, $user->friends_count, $user->profile_image_url];
        $baza->insert("Korisnik",$rows,$values);
        echo "<b>Username generisan za app: </b>".$username;
        echo "<br>"; 
        echo "<b>Password generisan za app: </b> ".$password;
        echo "<br>";
    }

?>
         
         <!--
             prikaz poslednjeg tvita korisnika i forma za kreiranje novog tvita
         -->
        <b>Poslednji tvit:</b>
        <br />
        <?php echo $user->status->text; 
              echo "<br> Datum: ".$user->status->created_at; 
        ?>
        <br>
        <br>
        <form action="index.php" method="post">
            <label for="tvit">What's happening?</label>
            <br>
            <br>
            <input id="tvit"type="text" name="tweet" placeholder="enter tweet here..." style="width:500px;height: 100px;">
            <button type="submit" name="submit-tweet">Tweet</button>
        </form>
        <?php 
        if(isset($_POST["submit-tweet"])) {
            if(strlen($_POST["tweet"]) <= 240 ) {
                $poruka = $_POST["tweet"];
                // slanje post zahteva za tvit
                $novi_tvit = $connection->post("statuses/update",["status" => $poruka]);       
            } else {
                echo "Tvit mora imati najvise 240 karaktera.";
            }
        }

        
        ?>
     <!-- logout -->
        <form action="logout.php" >
            <button type="submit" name="submit">Odjavi se</button>
        </form>
        <hr />
        <br />
        <h3>Home page</h3>
        <br>
        <hr />

        <br />
        <br />
        
        <?php 
        // prikaz najnovijih tvitova na tajmlajnu
        $statusi = $connection->get("statuses/home_timeline", ["count" => 10, "exclude_replies" => true]);
       
        foreach($statusi as $status) {
            $datum = $status->created_at;
            $datum_niz = explode(" ",$datum);
            $datum_final = $datum_niz[0].", ".$datum_niz[1]." ".$datum_niz[2]." / ".$datum_niz[3];
            echo $status->user->name.", @".$status->user->screen_name.", ".$datum_final."<br>";
            echo "<img src=\"";      
            echo $status->user->profile_image_url;      
            echo "\" />";
            echo "<br>";
            echo $status->text."<br><br>";
            echo "<hr>";
            
        }
     
        ?>
        <?php
    }
} else { 
     // not logged in, get and display the login with twitter link
     // prvi login je sa podacima sa tvitera, drugi login u formi prihvata generisane podatke i prikazuje korisnika iz baze
    $url = $connection->url( 'oauth/authorize', array( 'oauth_token' => $request_token['oauth_token'] ) );
    ?>
    <a href="<?php echo $url; ?>">Login With Twitter</a>
    
    <h2>Login With Given Credentials</h2>
    <form action="index.php" method="post">
        <input type="text" name="username" placeholder="username">
        <input type="text" name="password" placeholder="password">
        <button type="submit" name="submit-login"> Login</button>
    </form>
    <br>
    <br>
    <br>
    
    
    <?php
    if(isset($_POST["submit-login"])) {

        $baza = new Baza("imar");
        $username = $_POST["username"];
        $password = $_POST["password"];

        $korisnik = $baza->proveriKorisnika($username,$password);

        if(!isset($korisnik->UsernameTwitter)) {
            echo "Korisnik sa unetim podacima ne postoji u bazi.";
        }else {
            echo "<img src=\"$korisnik->Slika\" />";
            echo "<br>";
            echo "<b>Korisnik:</b>".$korisnik->Ime;
            echo "<br>";
            echo "<b>Korisnicko ime:</b>".$korisnik->UsernameTwitter;
            echo "<br>";
            echo "<b>Broj pratilaca:</b>".$korisnik->BrojPratilaca;
            echo "<br>";
            echo "<b>Prati:</b>".$korisnik->Prati;
            echo "<br>";
            echo "<form action=\"logout.php\" >
            <button type=\"submit\" name=\"submit\">Odjavi se</button>
            </form>";
        }

    }
}
?>