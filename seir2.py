import os
import subprocess

# Define metadata for channels
channel_metadata = {
    "bnt": {"name": "БНТ 1", "tvg_id": "BNT1", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt1.png"},
    #"bnt2": {"name": "БНТ 2", "tvg_id": "BNT2", "group_title": "Национални", "logo": "https://hajanddebono.com/images/bnt2.png"},
    "bnthd": {"name": "БНТ 3", "tvg_id": "BNT3", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt3.png"},
    "bnt4": {"name": "БНТ 4", "tvg_id": "BNT4", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt4.png"},
    "btv": {"name": "bTV", "tvg_id": "bTV", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/btv.png"},
    "nova": {"name": "Nova TV", "tvg_id": "Nova", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/nova.png"},

    "btvcinema": {"name": "bTV Cinema", "tvg_id": "bTVCinema", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvcinema.png"},
    "btvaction": {"name": "bTV Action", "tvg_id": "bTVAction", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvaction.png"},
    "btvcomedy": {"name": "bTV Comedy", "tvg_id": "bTVComedy", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvcomedy.png"},
    "btvlady": {"name": "bTV Story", "tvg_id": "bTVStory", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvstory.png"},
    "diema": {"name": "Diema", "tvg_id": "Diema", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/diema.png"},
    "diemaf": {"name": "Diema Family", "tvg_id": "DiemaFamily", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/diemafamily.png"},
    "kino": {"name": "Kino Nova", "tvg_id": "KinoNova", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/kinonova.png"},
    "fox": {"name": "Star Channel", "tvg_id": "STARChannel", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starchannel.png"},
    "foxcrime": {"name": "Star Crime", "tvg_id": "StarCrime", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starcrime.png"},
    "foxlife": {"name": "Star Life", "tvg_id": "StarLife", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starlife.png"},
    "epicdrama": {"name": "Epic Drama", "tvg_id": "EpicDrama", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/epicdrama.png"},
    "amc": {"name": "AMC", "tvg_id": "amc", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/amc.png"},
    "axn": {"name": "AXN", "tvg_id": "AXN", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/axn.png"},
    "moviestar": {"name": "MovieStar", "tvg_id": "MovieSTAR", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/moviestar.png"},
    "filmbox": {"name": "HBO", "tvg_id": "HBO", "group_title": "Филми", "logo": "https://ia804502.us.archive.org/33/items/logo.brootv_hbo/HBO.png"},
    "filmboxextra": {"name": "FilmBox Extra", "tvg_id": "FilmBoXtraHD", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/filmboxtrahd.png"},
    "filmboxplus": {"name": "FilmBox Stars", "tvg_id": "FilmBoxStars", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/filmboxstars.png"},

    "diemasport": {"name": "Diema Sport", "tvg_id": "DiemaSport", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport.png"},
    "diemasport2": {"name": "Diema Sport 2", "tvg_id": "DiemaSport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport2.png"},
    "diemasport3": {"name": "Diema Sport 3", "tvg_id": "DiemaSport3", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport3.png"},
    "novasport": {"name": "Nova Sport", "tvg_id": "NovaSport", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/novasport.png"},
    "eurosport": {"name": "Eurosport 1", "tvg_id": "Eurosport1", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/eurosport1.png"},
    "eurosport2": {"name": "Eurosport 2", "tvg_id": "Eurosport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/eurosport2.png"},
    "mtelsport": {"name": "MAX Sport 1", "tvg_id": "MAXSport1", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport1.png"},
    "mtelsport2": {"name": "MAX Sport 2", "tvg_id": "MAXSport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport2.png"},
    "mtelsport3": {"name": "MAX Sport 3", "tvg_id": "MAXSport3", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport3.png"},
    "mtelsport4": {"name": "MAX Sport 4", "tvg_id": "MAXSport4", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport4.png"},
    "ringbg": {"name": "RING BG", "tvg_id": "RING", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/ring.png"},

    "historytv": {"name": "History Channel", "tvg_id": "History", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/history.png"},
    "discovery": {"name": "Discovery Channel", "tvg_id": "Discovery", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/discovery.png"},
    "idx": {"name": "Investigation Discovery", "tvg_id": "ID", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/id.png"},
    "ng": {"name": "National Geographic", "tvg_id": "NatGeo", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/natgeo.png"},
    "natgeowild": {"name": "Nat Geo Wild", "tvg_id": "NatGeo Wild", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/natgeowild.png"},
    "animalplanet": {"name": "Animal Planet", "tvg_id": "AnimalPlanet", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/animalplanet.png"},
    "docubox": {"name": "Docubox", "tvg_id": "DocuBox", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/docubox.png"},
    "tlc": {"name": "TLC", "tvg_id": "TLC", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/tlc.png"},
    "24kitchen": {"name": "24Kitchen", "tvg_id": "24kitchen", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/24kitchen.png"},
    "viasat_history": {"name": "Viasat History", "tvg_id": "ViasatHistory", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/viasathistory.png"},
    "viasat_nature": {"name": "Viasat Nature", "tvg_id": "ViasatNature", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/viasatnature.png"},

    "cartoon": {"name": "Cartoon Network", "tvg_id": "CartoonNetwork", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/cartoonnetwork.png"},
    "disney": {"name": "Disney Channel", "tvg_id": "Disney", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/disney.png"},
    "nickelodeon": {"name": "Nickelodeon", "tvg_id": "Nickelodeon", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nickelodeon.png"},
    "nicktoons": {"name": "NickToons", "tvg_id": "Nicktoons", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nicktoons.png"},
    "nickjr": {"name": "Nick Jr", "tvg_id": "NickJr", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nickjr.png"},

    
    "planetahd": {"name": "Planeta HD", "tvg_id": "Planeta", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/planeta.png"},
    "planetafolk": {"name": "Planeta Folk", "tvg_id": "PlanetaFolk", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/planetafolk.png"},
    "tiankov-folk": {"name": "Tiankov Folk","tvg_id": "TiankovFolk", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/tiankovfolk.png"},
    #"tiankov-orient": {"name": "Tiankov Orient","tvg_id": "TiankovFolk", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/tiankovorient.png"},
    "fantv": {"name": "FEN TV", "tvg_id": "FENTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/fentv.png"},
    "balkanika": {"name": "Balkanika", "tvg_id": "Balkanika", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/balkanika.png"},
    "thevoice": {"name": "The Voice", "tvg_id": "TheVoice", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/thevoice.png"},
    "magictv": {"name": "Magic TV", "tvg_id": "MagicTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/magictv.png"},

    "hls": {"name": "Евроком", "tvg_id": "Eurocom", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/eurocom.png"},
    #"tracks-v1a1": {"name": "MTV", "tvg_id": "MTV", "group_title": "Чуждестранни", "logo": "https://e1.pngegg.com/pngimages/734/114/png-clipart-tv-channel-icons-mtv-withe-white-and-blue-mtv-logo-graphic.png"},
    "travel": {"name": "Travel TV", "tvg_id": "Travel", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/travel.png"},
    "thisisbulgaria": {"name": "This Is Bulgaria", "tvg_id": "thisisbg", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/thisisbg.png"},
    "autotv": {"name": "100 Auto Moto", "tvg_id": "AutoMotorSport", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/automotorsport.png"},
    #"k0": {"name": "Канал 0", "tvg_id": "Kanal0", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/kanal0.png"},
    #"zagoratv.ddns.net:8080": {"name": "TV Zagora", "tvg_id": "TVSTZ", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/tvstz.png"},
    "stream04": {"name": "Deutsche Welle", "tvg_id": "DeutscheWelle", "group_title": "Чуждестранни", "logo": "http://logos.epg.cloudns.org/deutschewelle.png"},
    "temp": {"name": "TV1", "tvg_id": "TV1", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/tv1.png"},
    "shorturl.at": {"name": "DSTV", "tvg_id": "DSTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/dstv.png"}



}



# File paths
input_file = "temp.txt"
output_file = "playlist.m3u"

# M3U Header
m3u_header = "#EXTM3U\n"

def generate_m3u():
    if not os.path.exists(input_file):
        print(f"Error: {input_file} does not exist.")
        return
    
    with open(input_file, "r") as infile:
        lines = infile.readlines()

    # Start building M3U content
    m3u_content = [m3u_header]
    
    for line in lines:
        url = line.strip()
        if not url:
            continue
        
        # Extract channel identifier from URL
        channel_id = url.split('/')[-2]
        metadata = channel_metadata.get(channel_id, {})
        
        # Fallbacks for missing metadata
        name = metadata.get("name", channel_id.capitalize())
        tvg_id = metadata.get("tvg_id", "")
        group_title = metadata.get("group_title", "Unknown")
        logo = metadata.get("logo", "")
        
        # Add channel entry to M3U content
        m3u_content.append(
            f'#EXTINF:-1 tvg-id="{tvg_id}" tvg-logo="{logo}" group-title="{group_title}",{name}\n{url}\n'
        )
    
    # Write to output file
    with open(output_file, "w", encoding='utf-8') as outfile:
        outfile.writelines(m3u_content)
    #print(f"{output_file} has been successfully created!")

#def call_ftp_script():
    # Define the path to the ftp.py script
    #script_path = os.path.join(os.getcwd(), 'ftp.py')
    
    # Run the ftp.py script using subprocess
    #subprocess.run(['python', script_path], check=True)

# Run the function
generate_m3u()
#call_ftp_script()
