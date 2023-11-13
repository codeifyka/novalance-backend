import backgroundImage from '../../assets/bg_client.png';
import logo from '../../assets/images/logo.png';
export default {
  setup() {
    function handleMouseOver(){
        console.log("hii")
    }
    function handleMouseOut(){
        console.log("hii")
    }
    
    return {
      backgroundImagePath: backgroundImage,logo,handleMouseOver,handleMouseOut
    };
  },
};
