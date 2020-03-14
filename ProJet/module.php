<?php
#TODO Message Sink einfügen
const 
	RGB_RED = 0,
	RGB_GREEN=1,
	RGB_BLUE=2;

class rgb {
	public static function fromInt(int $Color){
		return [ RGB_RED=>($Color >> 16) & 0xFF, RGB_GREEN=>($Color >> 8)  & 0xFF,	RGB_BLUE=> ($Color & 0xFF ) ];
	}
	public static function toInt(int $Red, int $Green , int $Blue){
		return ($Red << 16) + ($Green << 8) + $Blue;
	}
	public static function setLevel(array &$rgb, $NewLevel, $OldLevel=null){
		if(is_null($OldLevel))$OldLevel=round(max($rgb)/2.55);
		if($OldLevel==$NewLevel)return false;
		foreach($rgb as &$v){
			$v=round(($v/$OldLevel)*$NewLevel);
			if($v>255)$v=255;elseif($v>0)$v--;
		}	
		return true;
	}	
}

class ProJetX extends IPSModule {
	function Create(){
		parent::Create();
 		$this->RegisterPropertyInteger('DeviceID',144);
 		$this->RegisterPropertyInteger('Mode',0);
 		$this->RegisterPropertyInteger('DimSpeed',0);
 		$this->RegisterPropertyBoolean('DisableWhiteChannel', true);
 		$this->RegisterPropertyInteger('SyncClient',0);
 		
 		$this->RegisterAttributeInteger('LAST_COLOR', 0);
 		$this->RegisterAttributeInteger('LAST_WHITE', 0);
	}
	function ApplyChanges(){
		parent::ApplyChanges();
		$this->ConnectParent("{995946C3-7995-48A5-86E1-6FB16C3A0F8A}");
		$this->registerVariableBoolean('STATE',$this->Translate('State'),'~Switch',0);
 		$this->registerVariableInteger('LEVEL',$this->Translate('Level'),'~Intensity.100',1);
		$this->registerVariableInteger('COLOR',$this->Translate('Color'),'~HexColor',2);
		$actions = ['STATE','LEVEL','COLOR'];
		if($this->ReadPropertyBoolean('DisableWhiteChannel')){
			@$this->unregisterVariable('WHITE');
		}
		else {
			$actions[]='WHITE';
			$this->registerVariableInteger('WHITE',$this->Translate('White'),'~Intensity.255',9);
		}
 		if($this->ReadPropertyInteger('Mode')==0){ // Color Mode
			@$this->unregisterVariable('RED');
 			@$this->unregisterVariable('GREEN');
 			@$this->unregisterVariable('BLUE');
 		}else{ // RGB Mode
 			$rgb=rgb::fromInt($this->getValue('COLOR'));
 			if($id=$this->registerVariableInteger('RED',$this->Translate('Red'),'~Intensity.255',2))SetValue($id, $rgb[RGB_RED]);
 			if($id=$this->registerVariableInteger('GREEN',$this->Translate('Green'),'~Intensity.255',3))SetValue($id, $rgb[RGB_GREEN]);
 			if($id=$this->registerVariableInteger('BLUE',$this->Translate('Blue'),'~Intensity.255',4))SetValue($id, $rgb[RGB_BLUE]);
			$actions=array_merge($actions,['RED','GREEN','BLUE']);
 		}
		$this->_enableActions($actions);
		$this->_getSyncClientID();
		
  	}
	function RequestAction($ident, $value){
		switch($ident){
 			case 'STATE': $this->SetState($value); break;
   			case 'LEVEL': $this->SetLevel($value); break;
 			case 'COLOR': $this->SetColor($value); break;
   			case 'WHITE': $this->SetWhite($value); break;			   			
 			case 'RED'	: $this->SetRGBW($value, $this->getValue('GREEN'),$this->getValue('BLUE'),$this->getValue('WHITE')); break;
   			case 'GREEN': $this->SetRGBW($this->getValue('RED'),$value,$this->getValue('BLUE'),$this->getValue('WHITE')); 
   			case 'BLUE'	: $this->SetRGBW($this->getValue('RED'),$this->getValue('GREEN'),$value, $this->getValue('WHITE')); 
		}
	}
 	function ReceiveData($JSONString){
 		$this->SendDebug(__FUNCTION__,'Message::'.$JSONString,0);
 	}	 
	
 	function GetConfigurationForm(){
 		if($this->GetStatus()!=102){
 			$f=json_decode(file_get_contents(__DIR__.'/form.json'),true);
 			$f['status']=[
 				['code'=>300,'icon'=>'error','caption'=>$this->GetBuffer('LastError') ]	
 			];
 			return json_encode($f);
 		}
 	}
 	public function SetState(bool $StateOn){
 		if($this->getValue('STATE')===$StateOn)return true;
 		return $StateOn? $this->_upState():$this->_downState();
 	}
	
 	public function SetColor(int  $Color){
  		if($this->GetValue('COLOR')==$Color) return;
  		$rgb=rgb::fromInt($Color);
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, intval($this->getValue('WHITE')), $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], intval($this->getValue('WHITE')));	
  	}
 	public function SetLevel(int $DimLevel){
 		if($DimLevel>100)$DimLevel=100;elseif($DimLevel<0)$DimLevel=0;
  		if($this->GetValue('LEVEL')==$DimLevel) return true;
 		$color = $this->getValue('COLOR');
 		if(!$color)$color = $this->ReadAttributeInteger('LAST_COLOR');
 		
  		
  		$rgb=$color? rgb::fromInt($this->getValue('COLOR')):[128,128,128];
 		if(!rgb::setLevel($rgb, $DimLevel))return true;
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, intval($this->getValue('WHITE')), $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], intval($this->getValue('WHITE')));	
 	}
 	public function SetWhite(int $NewWhite){
 		if($this->ReadPropertyBoolean('DisableWhiteChannel')){
 			echo "White channel disabled";
 			return false;
 		}
 		if($NewWhite>255)$NewWhite=255;elseif($NewWhite<0)$NewWhite=0;
  		if($this->GetValue('WHITE')==$NewWhite) return true;
 		$rgb=rgb::fromInt($this->getValue('COLOR'));
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, $NewWhite, $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], $NewWhite);	
 	}
 	
 	public function SetRGBW(int $R, int $G, int $B, int $W){
		if($ok=$this->_forwardData(['P',$R,$G,$B,$W]))	{
			$this->_updateByColor($R, $G, $B,$W);
			if($clientID = $this->_getSyncClientID()){
				return PJX_SetRGBW($clientID,$R, $G, $B, $W);
			}
		}
		return (bool)$ok;
	}
 	public function DimRGBW(int $R, int $RTime, int $G, int $GTime, int $B, int $BTime, int $W, int $WTime){
		$toParam=function($v,$t){
			return $t>0?hexdec(sprintf('%02x%02x',$t,$v)):$v;
		};		
		$data=['P',$toParam($R,$RTime),$toParam($G,$GTime),$toParam($B,$BTime),$toParam($W,$WTime)];
 		if($ok=$this->_forwardData($data)){
			$this->_updateByColor($R, $G, $B,$W);
			if($clientID = $this->_getSyncClientID()){
				return PJX_DimRGBW($clientID,$R, $RTime, $G, $GTime, $B, $BTime, $W, $WTime);
			}
 		}
 		return (bool)$ok;
	}
	public function DimUp(){
		return (($level=$this->getValue('LEVEL'))<100) ? $this->SetLevel($level+5):true;
 	}
 	public function DimDown(){
		return (($level=$this->getValue('LEVEL'))>0) ? $this->SetLevel($level-5):true;
 	}
	
	public function RunProgram(int $Type){
		if($ok=$this->_forwardData(['F',$Type])){
			if($clientID = $this->_getSyncClientID()){
				return PJX_RunProgram($clientID,$Type);
			}
		}
		return (bool)$ok;
	}

	
	private function _enableActions(array $actions){
		foreach($actions as $a)$this->enableAction($a);
	}
	
	private function _getSyncClientID(){
		if($clientID = $this->ReadPropertyInteger('SyncClient')){
			if(!IPS_InstanceExists($clientID)){
				$msg= "SyncClient Instance $clientID missing";
			}elseif (IPS_GetInstance($clientID)['ModuleInfo']['ModuleID']!='{19650302-C001-0000-DE01-2020PRO10000}'){
				$msg="Select SyncClient Instance $clientID is not a ProJetX Module";
			}elseif($clientID == $this->InstanceID){
				$msg= "Selected SyncClient Instance $clientID can not Sync with self";
			}else{
				$this->SetBuffer('LastError','');
				if($this->GetStatus()==300){
					$this->SetStatus(102);
				}
				return $clientID;	
			}
			$this->SetBuffer('LastError',$msg);
			IPS_LogMessage(IPS_GetName($this->InstanceID),$msg);
			$this->SetStatus(300);
		} else {
			$this->SetBuffer('LastError','');
			if($this->GetStatus()==300){
				$this->SetStatus(102);
			}
		}
	}
	
 	private function _updateByColor($r,$g,$b,$w){
 		$oldColor = $this->getValue('COLOR');
 		$this->setValue('COLOR', $newColor=rgb::toInt($r, $g, $b));
 		$this->setValue('LEVEL', $level=round(max($r,$g,$b)/2.55));
 		if($newColor!=$oldColor){
 			$this->WriteAttributeInteger('LAST_COLOR',$newColor? $newColor: $oldColor);
 		}

 		if(!$this->ReadPropertyBoolean('DisableWhiteChannel')){
 			$oldWhite = $this->getValue('WHITE');
 			$this->setValue('WHITE', $w);
	  		if($w!=$oldWhite){
	 			$this->WriteAttributeInteger('LAST_WHITE', $w?$w:$oldWhite);
	 		}
 		}
 		$this->SetValue('STATE', $level!=0 || $w>0);
 		if($this->ReadPropertyInteger('Mode')>0){ // Splitt Mode
 			$this->setValue('RED', $r);
 			$this->setValue('GREEN', $g);
 			$this->setValue('BLUE', $b);
 		}
 	}
	private function _downState(){
		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			$this->DimRGBW(0, $dimSpeed, 0, $dimSpeed, 0, $dimSpeed, 0, $dimSpeed);			
		else 
			$this->SetRGBW(0, 0, 0, 0);
	}
	
	private function _upState(){
		$rgb=($onColor=$this->ReadAttributeInteger('LAST_COLOR'))?rgb::fromInt($onColor):[RGB_RED=>128,RGB_GREEN=>128,RGB_BLUE=>128];
 		$w= $this->ReadPropertyBoolean('DisableWhiteChannel')? 0: $this->ReadAttributeInteger('LAST_WHITE');
		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			$this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, $w, $dimSpeed);			
		else 
			$this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], $w);
	}
	
	private function _forwardData(array $value){
		$this->SendDebug(__FUNCTION__,'Send:'.implode(',',$value),0);
		$data['DataID']="{9DD17B0B-030F-4849-8BFF-88EB4BB414BA}";
		$data['Data']=$this->ReadPropertyInteger('DeviceID').','.implode(',',$value);
		$data=json_encode($data);
		$this->SendDebug(__FUNCTION__,'Send:'.$data,0);
		if(!$value=@$this->SendDataToParent($data)){
			IPS_LogMessage(__CLASS__,'Error sending data to Device');
			return true;
		} else	
			$this->SendDebug(__FUNCTION__,'Return:'.var_export($value,true),0);
		return $value;		
	}
	
	protected function setValue( $ident, $value){
		if( (($id=@$this->GetIDForIdent($ident)) && GetValue($id)!=$value))return SetValue($id,$value);
		return ($id>0);
	}
	protected function getValue( $ident){
		return ($id=@$this->GetIDForIdent($ident))?GetValue($id):null;
	}
 	
	
}
?>