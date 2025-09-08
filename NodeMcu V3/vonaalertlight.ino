/* steamakersblocks.com */
/* project: VONA monitor (nodemcu) */
/* author: Carlo Puglisi */

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <base64.h>

double pos_chiave_json;
double update_in_corso;
double lungh_valore_json;
String s_vona;
String s_alert2;
const char espwifi_ssid[]="***";
const char espwifi_pass[]="***";
unsigned long task_time_ms=0;

unsigned long task_time_ms2=0;

unsigned long task_esp32_stacksize=2048; //bytes
WiFiClient wifiClient;
HTTPClient http_client;

void espwifi_check(){
	if (WiFi.status() != WL_CONNECTED){
		WiFi.reconnect();
		delay(5000);
	}
}

void espwifi_setup(){
	WiFi.mode(WIFI_STA);
	WiFi.begin(espwifi_ssid,espwifi_pass);
	while (WiFi.status() != WL_CONNECTED) delay(500);
}

void wifi_connect() {
	analogWrite(2,(uint16_t)(255));
	analogWrite(0,(uint16_t)(255));
	analogWrite(4,(uint16_t)(255));
	espwifi_setup();
	Serial.println(String("WLAN: ")+String(WiFi.localIP().toString())+String(", ")+String(WiFi.RSSI())+String(" dBm"));
	analogWrite(2,(uint16_t)(51));
	analogWrite(0,(uint16_t)(51));
	analogWrite(4,(uint16_t)(255));
	update_in_corso = 0;
}
void wifi_info() {
	if (String(WiFi.RSSI()).equals(String("0"))) {
		wifi_connect();
	}

}
void Task_esp32_0(void *parameter){

	for(;;){
	vTaskDelay(3600000/ portTICK_PERIOD_MS);
	ESP.restart();
	}
}

String fnc_http_client_get(String _url, String _user, String _pass,bool _response, bool _redirect){
	String resp="";
	int respcode;
	if(_redirect){
		http_client.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
	}
	else{
		http_client.setFollowRedirects(HTTPC_DISABLE_FOLLOW_REDIRECTS);
	}
	http_client.begin(wifiClient, _url.c_str());
	if(_user!="" || _pass!=""){
		String auth = base64::encode(_user + ":" + _pass);
		http_client.addHeader("Authorization", "Basic " + auth);
	}
	respcode=http_client.GET();
	if(_response){
		if(respcode>0){
			resp=http_client.getString();
		}else{
			resp="HTTP-GET-ERROR: "+String(respcode);
		}
	}
	http_client.end();
	return resp;
}

String fnc_http_client_post(String _url, String _data, String _user, String _pass, String _contenttype, bool _response, bool _redirect){
	String resp="";
	int respcode;
	if(_redirect){
		http_client.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
	}
	else{
		http_client.setFollowRedirects(HTTPC_DISABLE_FOLLOW_REDIRECTS);
	}
	http_client.begin(wifiClient, _url.c_str());
	if(_user!="" || _pass!=""){
		String auth = base64::encode(_user + ":" + _pass);
		http_client.addHeader("Authorization", "Basic " + auth);
	}
	http_client.addHeader("Content-Type",_contenttype);
	respcode=http_client.POST(_data);
	if(_response){
		if(respcode>0){
			resp=http_client.getString();
		}else{
			resp="HTTP-POST-ERROR: "+String(respcode);
		}
	}
	return resp;
}

void vona_update() {
	s_vona = fnc_http_client_get(String("http://www.noexit.it/sniffetto/v1/vona"),String(""),String(""),true,true);
	pos_chiave_json = (String(s_vona).indexOf(String("current_color"))+1);
	if ((pos_chiave_json != 0)) {
		s_alert2 = String(s_vona).substring((((pos_chiave_json + 16)))-1,(String(s_vona).length()));
		lungh_valore_json = (String(s_alert2).indexOf(String("\","))+1);
		s_alert2 = String(s_alert2).substring((1)-1,((lungh_valore_json - 1)));
	}

	if (String(s_alert2).equals(String("GREEN"))) {
		Serial.println(String("Accendo il verde..."));
		analogWrite(2,(uint16_t)(51));
		analogWrite(0,(uint16_t)(204));
		analogWrite(4,(uint16_t)(0));
	}
	else if (String(s_alert2).equals(String("YELLOW"))) {
		Serial.println(String("Accendo il giallo..."));
		analogWrite(2,(uint16_t)(255));
		analogWrite(0,(uint16_t)(255));
		analogWrite(4,(uint16_t)(0));
	}
	else if (String(s_alert2).equals(String("ORANGE"))) {
		Serial.println(String("Accendo l\'arancione..."));
		analogWrite(2,(uint16_t)(255));
		analogWrite(0,(uint16_t)(102));
		analogWrite(4,(uint16_t)(0));
	}
	else if (String(s_alert2).equals(String("RED"))) {
		Serial.println(String("Accendo il rosso..."));
		analogWrite(2,(uint16_t)(204));
		analogWrite(0,(uint16_t)(0));
		analogWrite(4,(uint16_t)(0));
	}
	else {
		Serial.println(String("Colore non riconosciuto: ")+String(s_alert2));
		analogWrite(2,(uint16_t)(255));
		analogWrite(0,(uint16_t)(255));
		analogWrite(4,(uint16_t)(255));
	}

}

void setup()
{
  	pinMode(2, OUTPUT);
	pinMode(0, OUTPUT);
	pinMode(4, OUTPUT);

	Serial.begin(115200);
	Serial.flush();
	while(Serial.available()>0)Serial.read();

	Serial.println(String("###"));
	Serial.println(String("VONA monitor v0.1"));
	wifi_connect();

	xTaskCreatePinnedToCore(Task_esp32_0,"T0",task_esp32_stacksize,NULL,3,NULL,0);
}


void loop()
{
	yield();

	espwifi_check();

  	if((millis()-task_time_ms)>=3000){
  		task_time_ms=millis();
  		wifi_info();
  	}
  	if((millis()-task_time_ms2)>=10000){
  		task_time_ms2=millis();
  		analogWrite(2,(uint16_t)(153));
  		analogWrite(0,(uint16_t)(51));
  		analogWrite(4,(uint16_t)(153));
  		if ((update_in_corso == 0)) {
  			update_in_corso = 1;
  			vona_update();
  			update_in_corso = 0;
  		}

  	}

}