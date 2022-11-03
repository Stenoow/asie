import createMaki from "./foods/createMaki";
import createNigiriSalmon from "./foods/createNigiriSalmon";

export default class AssemblyBox{
    constructor(){
        this.mouse = new THREE.Vector2();
        this.raycaster = new THREE.Raycaster();
        this.setFood = false;
        this.setFoodType = null;
        this.positions = {};
    }

    initiateEventClick(renderer, scene, camera){
        renderer.domElement.addEventListener("click", (e) => {
            this.onClick(e, renderer, camera, scene);
        });

        document.getElementById('food-list').addEventListener('click', (e) => {
            let typeOfFood = e.target.dataset.type;
            if(typeOfFood !== undefined){
                this.setFood = true;
                this.setFoodType = typeOfFood;
            }
        });
    }

    onClick(event, renderer, camera, scene){
        let intersect = this.getIntersect(event, renderer, camera, scene);
        if(this.setFood){
            this.setFoodGeometry(intersect, scene);
        }else if(intersect !== undefined && intersect.object.name !== 'box'){
            console.log(intersect.object.position.x, intersect.object.position.z);
            scene.remove(intersect.object);
        }
    }

    setFoodGeometry(intersect, scene){
        // if client click out of the box the variable "setfood" back to button
        if(intersect === undefined){
            this.setFood = false;
        }else{
            let food;
            switch(this.setFoodType){
                case "1":
                    food = createMaki();
                    break;
                case "2":
                    food = createNigiriSalmon();
                    break;
                default:
                    food = createMaki();
                    break;
            }
            let x = Math.round(intersect.point.x);
            let z = Math.round(intersect.point.z);
            // If x is not even we do nothing otherwise then we check if x is negative if it is the case we add 1 to it
            // so that it puts it in pair and if not we remove 1 from it
            x = x % 2 !== 0 ? x < 0 ? x + 1: x - 1 : x;
            // same as x
            z = z % 2 !== 0 ? z < 0 ? z + 1: z - 1 : z;
            // for not confusing in the code x and z has been equals to 0 and not -0
            if(x === -0) x = 0;
            if(z === -0) z = 0;
            if(this.positions[x] === undefined)
                this.positions[x] = {};
            if(this.positions[x][z] === undefined || this.positions[x][z].name !== food.name){
                console.log(this.positions[x][z] !== undefined)
                if(this.positions[x][z] !== undefined){
                    scene.remove(this.positions[x][z]);
                    console.log(this.positions[x][z])
                }
                this.positions[x][z] = food;
                food.position.x = x;
                food.position.y = 1.25;
                food.position.z = z;
                scene.add(food);
            }
            console.log(this.positions)
        }
    }

    getIntersect(event, renderer, camera, scene){
        this.mouse.x = ( event.offsetX / renderer.domElement.clientWidth ) * 2 - 1;
        this.mouse.y = - ( event.offsetY / renderer.domElement.clientHeight ) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, camera);
        let intersects = this.raycaster.intersectObjects(scene.children, false);

        if (intersects.length > 0)
        {
            for(let i = 0; i < intersects.length; i++)
            {
                let selectedObject = intersects[i].point;
                if(selectedObject != null && selectedObject.hasOwnProperty('x') &&
                    selectedObject.hasOwnProperty('y') && selectedObject.hasOwnProperty('z'))
                {
                    return intersects[i];
                }
            }
        }
        return undefined;
    }
}